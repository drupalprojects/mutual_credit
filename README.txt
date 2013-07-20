This document contains the following

** Basic setup **
** Advanced usage **

This document is a work in progress and may not be entirely up-to-date!
For a high level account of the architecture and intention of this module,
please see http://matslats.net/mutual-credit-architecture-3

WARNING!
The intention behind this module is to foster a culter of experimentation, not to impose ideological constraints about how a money system should work.
Consequently it can be used as straight mutual credit, as the name implies, or for fiat or commodity currencies in which units are 'issued' into circulation from an account allocated for that purpose.
Nonetheless, fools rush in where angels fear to tread! Many lessons have already been learned about currency design and the process of encouraging communities to adopt them.
A badly managed money system can cause people to lose out and create bad feeling and resistance to future innovation, especially because few people understand how money actually works.

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
7. Migration from Drupal 6


1. Transaction workflow.
There is a simple system for defining transaction states and defining the permission callbacks for the operations to move between states.
By default, transactions are created in FINISHED state, and the UNDO operation is visible only to permitted users. Transactions should never be edited.
Note that currently, transactions can be deleted but not edited; a module could be created to do that.
There is a hook to declare transaction_operations and new transaction states, so you can code your own workflow.
For example the signatures module declares another state, 'pending', and 2 operations, 'sign' and 'sign off' (plus various other logic & config).
The module supports THREE undo modes.
- change 'state' to undone
- remove transaction completely
- write a counter-transaction and set both to state 'undone'


2. Currency Design
Pay careful attention to the 'issuance' because this can affect other features. Reputation currencies are possible but other solutions like userpoints and fivestar should also be considered.
Each transaction operation has a permission setting per currency. Those permission options can be extended using a hook.


3. Forms
Of course you can build your own forms using modules for creating transactions, but this powerful form builder is provided. Each form has its own address in the menu system, access control, and can be available as a block also.
The administrator can design forms in HTML for different purposes and different places in the site.
The HTML template contains tokens for each transaction property / field, elements not referenced by tokens are hidden and must have preset values. Properties and fields can be preset or otherwise configured also Note that most fields can also be 'stripped' which removes the outer box, making them easier to theme.
The date field is available (as a token only) to allow transactions to be backdated.
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
There is full integration with the entity module.
All transaction state changes, including creation must be done with a call to transactions_state()
The transaction_totals() function is mostly duplicated by the mcapi_index_views module, but external entity controllers will have difficulty integrating with views I should think.


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

7. Migration from Drupal 6
If a site is not being upgraded, but migrated from Drupal 6 to Drupal 7, the following query, run on the d6 data base can be used to pull the transaction data into csv format ready to import into Drupal 7 with the mcapi_import module.
All non-deleted transactions are assumed to be in 'finished' state.
If tweaking this query remember that the transaction states in d6 and d7 are different.
SELECT n.nid as xid, n.title as description, u1.mail as payer, u2.mail as payee, e.quantity, '1stparty' as type, 1 as state
FROM node n
LEFT JOIN mc_exchanges e ON n.nid = e.nid
LEFT JOIN users u1 on e.payer_uid = u1.uid
LEFT JOIN users u2 on e.payee_uid = u1.uid
WHERE e.state <> -1