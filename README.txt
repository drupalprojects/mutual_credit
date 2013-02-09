This document contains the following

** Basic setup **
** Advanced usage **

This document is a work in progress and may not be entirely up-to-date!
For a high level account of the architecture and intention of this module,
please see http://matslats.net/mutual-credit-architecture-3

WARNING!
The intention behind this module is to foster a culter of experimentation, not to impose ideological constraints about how a money system should work.
Consequently it can be used as straight mutual credit, as the name implies, or for fiat or commodity currencies in which units are 'issued' into circulation from an account allocated for that purpose.
Nonetheless, be warned; many lessons have already been learned about currency design and the process of encouraging communities to adopt them.
Fools rush in where angels fear to tread!
A badly managed money system can cause people to lose out and create bad feeling and resistance to future innovation.

***********************
**  BASIC SETUP   **
***********************

Where defaults have been appropriate, the timebanking model has been preferred.

ENABLE MODULES
Enable Mutual Credit API, Mutual Credit transaction forms (which depends on specially created module, user_chooser), and Views integration
Optionally enable the other modules in the Complementary Currencies Section.
The present author has also written, based on the needs of many groups
uid_login - for LETS groups who commonly use their User ID
autocategorise - a way of bringing consistency to items provided by many users in a closed vocabulary
offers_wants - a simple-to-setup directory of categorised offers (and wants)

Visit admin/accounting/currencies
Each currency has its own config, theming and users
The accounting is all done to 2 decimal places, so any fractions of a unit are counted in hundredths.
Rather than writing hundreds on the transaction form, it can be configured to accept just certain fractions, like quarters (of an hour)
Note how the default settings are the strictest accounting standards possible, with no editing and no deleting of transactions

Visit admin/accounting/fields
Notice that transaction entity is fieldable. Fields added in this way will automatically be available to the form template and in views
Its possible to add a description, or a date, or an image or categories to your transaction object
Ensure the modules which declare those fields are installed and add and configure those fields here.
Note the 'cardinality' field in the field_worth settings. This allows you to have one or more currencies per transaction.

Visit admin/accounting/forms
Design the forms for users to create transactions
Like views, they can be exported and bundled in code with a distribution.
N.B I advise editing an existing one before creating a new one since it is possible to create invalid transaction forms.
Create menu links to the forms and/or wrap them in blocks.
Note that some modules alter existing forms or create new ones.
It should be possible to create forms in a few minutes for specific purposes.

Visit admin/accounting/transact
This is the troubleshooting transaction creation form, intended for administrative use only, it allows filtering on all transaction fields.

Visit admin/accounting/transactions
Using lots of views exposed filters allopws basic but thorough transaction management.
Notice that views queries even to this table respect the currency access control settings - this table could be exposed to users.

Visit admin/people/permissions
Note that these are general permissions, but there is a whole per-currency permission system

Limits
Most projects require that accounts have 'overdraft' limits and, in mutual credit, positive balance limits.
Enable the limits module and edit the currency and choose how you want the limits to be determined.

Views
The extra views module creates a transaction index table which is good for producing transaction summaries.
Some default views are provided with many helpful displays.
Be careful not to mix the handlers of the main transaction table with the index table
More display plugins would help to make these more attractive.
Integration with Google Charts or equivalent is needed.
Note that there isn't access control on this table as individual transactions are not meant to be shown. it is more intended for aggregting for aggregating.
This module does not provide special access control for transactions to be aggregated. However the view access control should be adequate
The previous version of the module contained a cache table containing balances and suchlike, but here it is all dynamic,
or at least drupal/views caching needs to be used, especially on large systems

Pending transactions
Causes transactions to be saved in a pending state, listing signatories.
Configure which transactions to effect, what signatures are needed, and notification on admin/accounting/signatures

Now you can start assembling the pieces according to the needs of your site.
The first level of architecture is in menus, blocks, views, mcapi_forms
For more ideas visit demo.communityforge.net
The cforge_custom installation profile which makes the demo module, is available her http://code.google.com/p/cforge-custom/

N.B. Entity API module http://drupal.org/project/entity
At time of writing, Feb 2012, Entity module looks like it is the way forward. However my attempt to depend on it has failed owing to poor documentation. Since all the CRUD controls are already written, views handlers also, there is little benefit to reworking everything to suit entity API at present.


***********************
**  ADVANCED TIPS    **
***********************
Intended for webshops and skilled Drupal developers

1. Transaction Worflow
2. Currency design
3. Actions and RUles
4. Internal API
5. Limits system
6. Views integration


1. Transaction workflow.
There is a simple system for defining transaction states and defining the permission callbacks for the operations to move between states.
By default, transactions are created in FINISHED state, and the UNDO operation is visible only to permitted users. Transactions should never be edited.
The signatures module declares another state, 'pending', and 3 operations, 'sign', 'sign off' and 'delete pending', and provides lots more logic and displays and some configuration around that.

2. Currency Design
The currencies are full (ctools) configuration objects and contain a lot of information about behaviour and access control.
There are three kinds of access control,
- currency_access, which determines who can trade with, and see aggregated data in that currency
- transaction_view_access, which gives different visibility for each transaction state.
- transaction_operations which allow users to move transactions between states.
An extensible series of callbacks is provided to give fine-grained control. The signature module provides such callbacks, for example so that only a user who needs to sign a transaction can access the 'sign' operation.

3. Forms
Of course you can build your own forms using modules for creating transactions, but this powerful form builder is provided. Each form has its own address in the menu system, access control, and can be available as a block also.
The administrator can design forms in HTML for different purposes and different places in the site.
The HTML template contains tokens for each transaction property / field, or excluded elements are hidden.
Properties and fields can be preset or otherwise configured also
The form has an optional confirmation page, the format of which can also be determined.

3. Actions and rules.
Three actions are provided by the core module,
- to send an email when a transaction enters 'completed' state
- to supplement a transaction cluster with another transaction - useful for taxation.
- to create a transaction based on the passed $entity->uid

Plus there are triggers for all transaction workflow operations.
Rules attempts to re-deply the them, but I got stuck describing the worth field to entity_token.
So for now, rules integration only works for events and conditions not involving field 'worth', but not transaction actions.

4. Internal API

The transaction entity has 3 forms
Simple entity object, usually called $transaction
Cluster, which is an array of transactions being passed around before being written with the same serial number, always called $cluster
Loaded entity, in which the dependent transactions (with the same serial number) are loaded into $entity->dependents, usually called $transaction
Don't forget all the transactions on their way to the theme system, which might be called $build or $transaction

Standard accounting database operations are conducted through an API described in transaction.api.php
Entity module is used where I could make sesnse of it.
The intention is that the entity controller can be swopped so that transactions can live in different formats in different databases.
That concept has been proved, but more needs to be done to allow each currency to have its own transaction controller.
All transaction state changes, including creation must be done with a call to transactions_state()
The transaction_totals() function is mostly duplicated by the mcapi_index_views module, but external entity controllers will have difficulty integrating with views I should think.
The module supports THREE undo modes which need to move to the transaction entity controller where they will be selected per currency.
- change 'state' to undone
- remove transaction completely
- write a counter-transaction and set both to state 'undone'

5. Limits system

During the accounting_validate phase the limits module checks whether the transaction cluster would put any users over their limits.
Limits are determined by callbacks, per currency, and are not saved in the database.
Limts can optionally be overridden by user profile settings and custom modules can add more callbacks
Under some circumstances limits could be exceeded, such as with automated transactions, or user 1 initiated transactions.
In that case only transactions will be permitted which bring users towards zero.
The limits module provides blocks to show 
- the balance and the min/max limits
- the amount which can be earned or spent before limits are hit.

6. Views integration
Much work has been done on views to give the site builder maximum flexibility.
First of all the transaction properties are exposed, and most of them as filters, arguments, sorts.
All the transaction_view_access callbacks have versions for modifying transaction views.
The mcapi_index_views module does what the transaction table cannot do, by installing a mysql VIEW and providing views integration with that. This allows a whole new perspective on the transactions, and allows new forms of statistics also.

