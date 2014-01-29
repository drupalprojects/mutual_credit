This document contains the following

** Basic setup **
** Advanced usage **

For an architectural description see 
http://matslats.net/mutual-credit-architecture-4

This document is a work in progress and may not be entirely up-to-date!

WARNING!
The intention behind this module is to foster a culter of experimentation, not to impose ideological constraints about how a money system should work.
Consequently it can be used as straight mutual credit, as the name implies, or for fiat or commodity currencies in which units are 'issued' into circulation from an account allocated for that purpose.
Nonetheless, fools rush in where angels fear to tread! Many lessons have already been learned about currency design and the process of encouraging communities to adopt them.
A badly managed money system can cause people to lose out and create bad feeling and resistance to future innovation, especially because few people understand how money actually works.

***********************
**  BASIC SETUP   **
***********************

ENABLE MODULES
Enable Community Accounting API, Firstparty, Views, and possibly rules
Optionally enable the other modules in the Complementary Currencies Section.

On admin/accounting are the major architectural elements
A small system will comprise one exchange, one currency, and one wallet per user.
In a larger system a user can be in many exchanges and exchanges can contain many currencies.
Transactions reference both exchanges and currencies.
Any bundle which has an entity_reference field referencing exchanges (like the user entity) can own wallets 
Because of these relationships it can problematic to disable or delete exchanges and currencies.
Wallets cannot be deleted, and transactions can only be (with the default storage controller) erased.

Visit admin/accounting/currencies and configure your first currency. 
The currency 'type' refers to how the integers in the database are converted and displayed.

Visit admin/accounting/transactions
Here you can access a full transaction form and enter a raw transaction. Normal users should never do this.
Notice that transaction entity is fieldable
Its possible to add a description, or a date, or an image or categories to your transaction object
You can see the 'states' and 'types' which comprise the workflow map
The transaction 'type' determines the 'start state' and hence the workflow path.
When you 'configure workflow' you can see the 'operations' which are the workflow vectors.
Each operation is fully configurable for you to decide the user experience.
If you have the 1stparty_forms module enabled, which you should, you can design forms for users to use under different circumstances.

Explore admin/accounting/misc
Note that by default the site runs in mixed_mode which means transactions can contain multiple currencies.

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

Now you can start assembling the pieces according to the needs of your site.
The first level of architecture is in menus, blocks, views, mcapi_forms
For more ideas visit demo.communityforge.net
The cforge_custom installation profile which makes the demo module, is available her http://code.google.com/p/cforge-custom/

***********************
**  ADVANCED TIPS    **
***********************
Intended for Drupal developers

1. Transaction processing hooks
2. Transaction Worflow
3. Firstparty Forms
4. Limits system
5. Views integration

1. Transaction processing hooks

2. Transaction workflow.
There is no built in way to 'edit' transactions, since such operations should be strictly controlled.
There is a hook system for defining transaction states and defining the permission callbacks for the operations to move between states.
By default, transactions are created in FINISHED state, and the 'undo' operation is visible only to permitted users.
The signatures module shows how a transaction workflow can be created using operations, states, and $transaction->type
It declares another state, 'pending', and 2 operations, 'sign' and 'sign off' (plus various other logic & config).
Operations show on the transaction as a field, and work through ajax. Each operation defined in hook_transaction_operations specifies the strings and callbacks needed. Each one determines under what circumstances it should appear and has an opportunity to inject elements into the confirm_form.

3. Firstparty forms
Of course you can build your own forms using modules for creating transactions, but this powerful form builder is provided. Each form has its own address in the menu system, access control, and can be available as a block also.
The administrator can design forms in HTML for different purposes and different places in the site.
The HTML template contains tokens for each transaction property / field, elements not referenced by tokens are hidden and must have preset values. Properties and fields can be preset or otherwise configured also Note that most fields can also be 'stripped' which removes the outer box, making them easier to theme.
The date field is available (as a token only) to allow transactions to be backdated.
The form has an optional confirmation page, the format of which can also be determined.

4. Limits system

During the accounting_validate phase the limits module checks whether the transaction cluster would put any users over their limits.
Limits are determined by callbacks, per currency, and are not saved in the database.
Limts can optionally be overridden by user profile settings and custom modules can add more callbacks
Under some circumstances limits could be exceeded, such as with automated transactions, or user 1 initiated transactions.
In that case only transactions will be permitted which bring users towards zero.
The limits module provides blocks to show
- the balance and the min/max limits
- the amount which can be earned or spent before limits are hit.

5. Views integration
Much work has been done on views to give the site builder maximum flexibility.
First of all the transaction properties are exposed, and most of them as filters, arguments, sorts.
The transactionAccess plugins have 2 functions, one for normal access checks and one for views
The mcapi_index_views table does what the transaction table allows a whole new perspective on the transactions, and allows new forms of statistics also.

