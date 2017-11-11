This document contains the following

** Basic setup **
** Advanced usage **

For an architectural description see
http://matslats.net/mutual-credit-architecture-4

This document is a work in progress and may not be entirely up-to-date!

WARNING!
The intention behind this module is to foster a culter of experimentation, not to impose ideological constraints about how a money system should work.
Consequently it can be used as straight mutual credit, as the name implies, or for fiat or asset currencies in which units are 'issued' into circulation from an account allocated for that purpose.
Nonetheless, fools rush in where angels fear to tread! Many lessons have already been learned about currency design and the process of encouraging communities to adopt them.
A badly managed money system can cause people to lose out and create bad feeling and resistance to future innovation, especially because few people understand how money actually works.

***********************
**  BASIC SETUP   **
***********************

ENABLE MODULES
Enable Community Accounting API, Forms designer, Views, and possibly rules
Optionally enable the other modules in the Community Accounting Section.

On admin/accounting are the major architectural elements
A small system such as a LETS or timebank will comprise one exchange, one currency, and one wallet per user.
In a larger system a user can be in many exchanges and exchanges can contain many currencies.
Any entity which has an entity_reference field referencing exchanges (like the user entity) can own wallets
Wallets which have transacted cannot be deleted, but if the holder entity is deleted, The wallet can be transferred to the holder entity's exchange

Visit admin/accounting/currencies and configure your first currency.
The currency 'type' refers to how the integers in the database are converted and displayed.

Visit admin/accounting/transactions
Here you can access a full transaction form and enter a raw transaction.
Normal users should always enter transactions using a more appropriate form, such as provided by the forms designer module.
Its possible to add a description, or a date, or an image or categories to your transaction object
You can see the 'states' and 'types' which comprise the workflow map
The transaction 'type' determines the 'start state' and hence the workflow path.
When you 'configure workflow' you can see the 'actions' which are the workflow vectors.
Each action is fully configurable for you to decide the user experience.
Use the mcapi_forms module to design forms for users to use under different circumstances.

Explore admin/accounting/misc

Limits
Most projects require that accounts have 'overdraft' limits and, in mutual credit, positive balance limits.
The limits module provides a new section on the currency settings page
Also if personal overrides are enabled, those are configured on the user profile.

Views
The extra views module creates a transaction index table which is good for producing transaction summaries.
Some default views are provided with many helpful displays.
Be careful not to mix the handlers of the main transaction table with the index table
More display plugins would help to make these more attractive.
Integration with Google Charts or equivalent is needed.
Note that there isn't row-level access control on the index table.
Views should either show aggregated data which tends to be less sensitive, or manage access per display.

Now you can start assembling the pieces according to the needs of your site.
The first level of architecture is in menus, blocks, views, mcapi_forms
For more ideas visit demo.communityforge.net

Internal Intertrading.
Intertrading is the name used to describe what happens when one exchange extends credit to another to facilitate payment between members of different exchanges.
CES and hOurWorld both have the ability intertrade amongst their own exchanges. I call this internal intertrading.
For simplicity's sake, internal intertrading is not possible in this module.

External intertrading happens between web servers via a 3rd party web service.
Currently the only such service available is Clearing Central, provided by CES.
We are working on another service provisionally called the Credit Commons.
@see http://creditcommons.net

***********************
**  ADVANCED TIPS    **
***********************
Intended for Drupal developers

1. Transaction processing hooks
2. Transaction Worflow
3. Forms designer
4. Limits system
5. Views integration

1. Transaction processing hooks

2. Transaction workflow.

Workflow is built up using mcapi_type and mcapi_state entities, with a special type of action being used as the workflow transition
By default, transactions are created in FINISHED state, and the 'undo' transition is visible only to permitted users.
The signatures module shows how transaction workflow can be augmented with a new type, a new state, and two actions
These actions are also used for entity operations

3. Forms designer
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
The mcapi_index_views table does what the transaction table allows a whole new perspective on the transactions, and allows new forms of statistics also.

