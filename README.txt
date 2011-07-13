This document contains the following

** How to set up the complementary currencies package **
** For advanced users **
** Testing procedure **

This is a work in progress and may not be entirely up-to-date!
For a generalised architectural description, please see http://matslats.net/mutual-credit-architecture-3


***********************
**   HOW TO SET UP   **
***********************

ENABLE MODULES
Enable Mutual Credit API, Mutual Credit transaction forms (which depends on user_chooser), and Views integration
Optionally enable the other modules in the Complementary Currencies Section.
Also consider:
uid_login - written by the present author for LETS groups who commonly use their User ID
autocategorise - written by the present author
and user_tabs - which the user/%/edit tabs under the Account tab, leaving more room for tabs on the first level

Visit admin/accounting
Visit admin/people/permissions
Notice that you can add fields (including taxonomy) to transactions: admin/accounting/transaction/edit/fields

Webform workflow
Go to the webforms config and familiarise yourself. Webforms contain some of the rules.
Each webform needs its own menu callback which gives it a title and some access control.
The webform accepts a transaction in certain states.
The webform fields are populated by the transaction and can be overridden by fixed values.
Then you provide the html to provide the exact layout you want for the webform template, confirmation page if required, and email notification if required.
Use tokens in place of the form elements.
Each field will do its own validation, then there is form-level validation.
If there is a confirmation page, it will take the whole page, even if the original form was in a block.
You can tell the webform which page to redirect to.
It should be possible to create webforms quickly and for specific purposes
Admin can peruse transactions on admin/accounting/transactions if views is enabled

Views
The base module provides all the fields for the main transaction table and one view with all filters exposed for the accountant.
The extra views module creates a transaction index table which is good for producing transaction summaries.
Some default views are provided with many helpful displays.
More display plugins would help to make these more attractive.
Note that there isn't access control on this table as individual transactions are not meant to be shown.
The previous version of the module contained a cache table containing balances and suchlike, but here it is all dynamic,
or at least drupal/views caching needs to be used, especially on large systems


Further set up

Email notifications
The mail notification module adds a tab to the webforms which allows composition of the email template for each webform used.
It also adds a notification option for each party in the webform.
Users themselves on their user profiles decide when to be notified.

Now you can consider the architecture. 
This module attempts to provide usable defaults, based loosely on the timebanking model.
The first level of architecture is in menus, blocks, views, webforms
For more ideas visit demo.communityforge.net
The cforge_custom installation profile which makes the demo module, is available her http://code.google.com/p/cforge-custom/

Comments on building your own money system
The intention behind this module is to foster a culter of experimentation, not to impose ideological constraints about how a money system should work.
Consequently it can be used as straight mutual credit, as the name implies, or for fiat or commodity currencies in which units are 'issued' into circulation from an account allocated for that purpose.
Nonetheless, be warned; many lessons have already been learned about currency design and the process of encouraging communities to adopt them.
Fools rush in where angels fear to tread!
A badly managed money system can cause people to lose out and create bad feeling and resistance to future innovation.


***********************
** BUILDING YOUR OWN **
***********************

1. Triggering a transaction
2. Storing the transactions elsewhere
3. Intertrading



***********************
** TESTING PROCEDURE **
***********************

