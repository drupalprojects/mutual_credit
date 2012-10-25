This document contains the following

** Basic setup **
** Advanced setup **
** Testing procedure **

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

Visit admin/accounting/transaction/fields
Notice that transaction entity is fieldable. Fields added in this way will automatically be available to the form template and in views
Its possible to add a description, or a date, or an image or categories to your transaction object
Ensure the modules which declare those fields are installed and add and configure those fields here.
Note the 'cardinality' field in the field_worth settings. This allows you to have more than one currency per transaction.

Visit admin/accounting/forms
Design the forms for users to create and edit transactions
Like views, they can be exported and bundled in code with a distribution.
N.B I advise editing an existing one before creating a new one since it is possible to create invalid transaction forms.
Create menu links to the forms and/or wrap them in blocks.
Note that some modules alter existing forms or create new ones.
It should be possible to create forms in a few minutes for specific purposes.

Visit admin/accounting/transact
This is the troubleshooting transaction create/edit form, intended for administrative use only, it allows all fields to be edited.

Visit admin/accounting/transactions
Using lots of views exposed filters allopws basic but thorough transaction management.
Notice that views queries even to this table respect the currency access control settings - this table could be exposed to users.

Visit admin/people/permissions
Note that these are general permissions, but there is a whole per-currency permission system

Limits
Most projects require that accounts have 'overdraft' limits and, in mutual credit, positive balance limits.
There is a module for that.
Edit the currency and choose how you want the limits to be determined.

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

Actions framework
Two actions are provided by the core module,
- to send an email when a transaction enters 'completed' state
- to supplement a transaction cluster with another transaction - useful for taxation.

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
**  ADVANCED SETUP   **
***********************

Intended for webshops and skilled Drupal developers


1. Internal API
2. Master/Slave transaction storage & entity controller
3. Triggering payments.

1. Internal API

Standard transaction operations are conducted through an API provided in mcapi.module
This API is for communicating with the swoppable entity controller.
All transaction state changes, including creation must be done with a call to transactions_state()
The transaction_totals() function is mostly duplicated by the mcapi_index_views module
Careful there are three undo modes.
There are a couple of wrapper functions round the API supporting transaction clusters
A cluster is when many transactions share a serial number, say if, they are spawned from it.
The module assumes the first transaction in a cluster is the main one, say for display purposes.

2. Master/Slave transaction storage & entity controller

Drupal allows several database connections to be defined in settings.php.
On admin/accounting/entity_controller you can choose which database connections the default transaction controller will write to
And which one connection it will read from.
Alternatively another entity controller can be written. Although the API isn't fully documented.

3. Triggering Payments

The mcapi module provides an action which adds a transaction to the cluster.
however it's not very configurable - the rules framework is more appropriate for this; encouragement is needed


***********************
** ON THE WISH LIST **
***********************
SMS - waiting for the sms_framework module
Voice activated transactions
iphone app
Google charts

