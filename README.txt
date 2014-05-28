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
Optionally enable the other modules in the Community Accounting Section.

On admin/accounting are the major architectural elements
A small system such as a LETS or timebank will comprise one exchange, one currency, and one wallet per user.
In a larger system a user can be in many exchanges and exchanges can contain many currencies.
Any entity which has an entity_reference field referencing exchanges (like the user entity) can own wallets
Wallets which have transacted cannot be deleted, but if the owner entity is deleted, ownership can be transferred to the owner entity's exchange

Visit admin/accounting/currencies and configure your first currency. 
The currency 'type' refers to how the integers in the database are converted and displayed.

Visit admin/accounting/transactions
Here you can access a full transaction form and enter a raw transaction. 
Normal users should always enter transactions using a more appropriate form, such as provided by the 1stparty module.
Notice that transaction entity is fieldable
Its possible to add a description, or a date, or an image or categories to your transaction object
You can see the 'states' and 'types' which comprise the workflow map
The transaction 'type' determines the 'start state' and hence the workflow path.
When you 'configure workflow' you can see the 'transitions' which are the workflow vectors.
Each transition is fully configurable for you to decide the user experience.
Use the 1stparty_forms module to design forms for users to use under different circumstances.

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
CES and hOurWorld both intertrade amongst their own exchanges. I call this internal intertrading.
External intertrading is more complex and requires a REST API - this is another project.
Here I describe how internal intertrading works in this module.
Any entity which is in an exchange can own a wallet....
The 1stparty transaction form 'partner' widget offers the user a list of currencies and a list of wallets to trade with, filtered according to whether intertrading is enabled for that form.
It is possible, but only the most complex configurations would allow, to choose a partner wallet and currency which are not compatible.
The transaction validation first decides which exchange is the 'source' of the transaction.
It compares the currencies in the transaction with the currencies of all the exchanges of which the wallet owner is a member.
If the partner wallet's parent is not in that echange, then it will try to split the transaction into 2.
From the source wallet to the source exchange's _intertrading wallet, and a payment in a different currency from another exchanges _intertrading wallet to the destination wallet.
So in a similar way it searches through destination wallet's exchanges to find one with an 'open' currency, that is, one with a exchange rate specified.
If the transaction has many currencies, it will be reject if more than one currency needs to be converted - that scenario would have pushed me over the edge of sanity.
So it should be possible for me in England to pay another friend in England in 3 currencies like this:
1 hour
1 virtual pound
10 Community Coins
and for my friend to receive:
1 hour
1 virtual pound
20 Double Dinars
But not to pay my friend in France because the system has no way of knowing that $virtual map to EURvirtual while CC maps DD.
This could be implemented however by adding a 'type' field to the currency.
This approach to exchange is quite complex but should work in wide range of configurations.
An exchange can allow many currencies
A wallet owner can be in many exchanges
A wallet owner can have many wallets
A transaction can consist of many currencies
Currencies can be confined to certain exchanges or set free.


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
There is no built in way to 'edit' transactions, since such transitions should be strictly controlled.
There is a hook system for defining transaction states and defining the permission callbacks for the transitions to move between states.
By default, transactions are created in FINISHED state, and the 'undo' transition is visible only to permitted users.
The signatures module shows how a transaction workflow can be created using transitions, states, and $transaction->type
It declares another state, 'pending', and 2 transitions, 'sign' and 'sign off' (plus various other logic & config).
Transitions show on the transaction as a field, and work through ajax. Each transition defined in hook_transaction_operations specifies the strings and callbacks needed. Each one determines under what circumstances it should appear and has an opportunity to inject elements into the confirm_form.

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
The mcapi_index_views table does what the transaction table allows a whole new perspective on the transactions, and allows new forms of statistics also.

