This document contains the following

** Basic setup **
** Advanced setup **
** Testing procedure **

This is a work in progress and may not be entirely up-to-date!
For a high level account of the architecture and intention of this module,
please see http://matslats.net/mutual-credit-architecture-3

WARNING!
The intention behind this module is to foster a culter of experimentation, not to impose ideological constraints about how a money system should work.
Consequently it can be used as straight mutual credit, as the name implies, or for fiat or commodity currencies in which units are 'issued' into circulation from an account allocated for that purpose.
Nonetheless, be warned; many lessons have already been learned about currency design and the process of encouraging communities to adopt them.
Fools rush in where angels fear to tread!
A badly managed money system can cause people to lose out and create bad feeling and resistance to future innovation.


Where defaults have been appropriate, the timebanking model has been preferred

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

Visit admin/accounting/currency
There is one currency in the system until the 'currency constellation' module is installed.
Edit its properties carefully.
The accounting is all done to 2 decimal places, so any fractions of a unit are counted in hundredths.
Rather than writing hundreds on the transaction form, it can be configured to accept just certain fractions, like quarters (of an hour)
Note how the default settings are the strictest accounting standards possible, with no editing and no deleting of transactions
When editing the accounting standards, the access operations (edit, delete) appear as extra tabs. Careful the AJAX is a bit flakey on that form.

Visit admin/accounting/webforms
No similarity intended to the http://drupal.org/project/webform
Design the forms for users to create and edit transactions
N.B It is possible to create invalid webforms, and I advise editing an existing one before creating a new one.
Create menu links to the forms and/or wrap them in blocks.
Note that some modules alter existing forms or create new ones.
Like views, you can create new ones from scratch or modify or clone the ones provided. They can be exported and bundled in code with a distribution.
The webform essentially moves transactions between states, offering an opportunity to show or edit the values.
However if is not a serious workflow system.
Each field will do its own validation, then there is form-level validation.
If there is a confirmation page, it will take the whole page, even if the original form was in a block.
You can tell the webform which page to redirect to.
It should be possible to create webforms in a few minutes for specific purposes
Would appreciate some feedback on the whole form creation process.

Visit admin/accounting/record
This is the troubleshooting transaction create/edit form, intended for administrative use only, it allows all fields to be edited.

Visit admin/accounting/transactions
Super views exposed filterama offers basic but thorough transaction management.
Notice that views queries to this table respect the currency 'view' permission settings

Visit admin/people/permissions
Note that these are general permissions, but there is a whole internal permission system in the currency definitions

Visit admin/accounting/transaction/edit/fields
Notice that transaction entity is fieldable. Fields added in this way will automatically be available to the webform template and in views
You can add fields (including taxonomy) to transactions: admin/accounting/transaction/edit/fields

Limits
Most projects require that accounts have 'overdraft' limits and, in mutual credit, positive balance limits.
There is a module for that.
Edit the currency and choose how you want the limits to be determined.
Discretionary limits will create an extra section on the user account edit form, inheriting defaults from the currency.


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


Further set up

Email notifications
The mail notification module adds a tab to the webforms which allows composition of the email template for each webform used.
It also adds a notification option for each party in the webform.
Users themselves on their user profiles decide when to be notified.
Do test this, espcially when used in conjunction with the pending module

Now you can start assembling the pieces according to the needs of your site.
The first level of architecture is in menus, blocks, views, webforms
For more ideas visit demo.communityforge.net
The cforge_custom installation profile which makes the demo module, is available her http://code.google.com/p/cforge-custom/


***********************
**  ADVANCED SETUP   **
***********************

Intended for webshops and skilled Drupal developers

1. Triggering payments.
2. Master/Slave transaction storage
3. internal API
4. Exporting currencies and webforms
5. i18n
6. theming


***********************
** ON THE WISH LIST **
***********************
SMS
Voice activated transactions
iphone app
Intertrading
Google charts
