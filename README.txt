This document is 4 documents in one:

** How to set up the Complementary currencies package **
** Generalised architecture of this mutual credit system **
** For advanced users **
** Testing procedure **


***********************
**   HOW TO SET UP   **
***********************

ENABLE MODULES
Enable transactions module, views
Optionally enable the other modules in the Complementary Currencies Section.
PHP_filter will enables default user friendly links at the bottom of some views, which you could easily change
Also consider uid_login and autocategorise, and user_tabs

Transactions
Setup your currency and other options at admin/marketplace and the pages under it.

Permissions
Don't forget to define the permissions AFTER naming the transaction-types for these modules at admin/user/permissions.
Owing to technical limitations, all trading users must have permission to 'view all transactions'

Complementary Currencies should now function. However before your site makes sense, you'll need to add some users and data.
Check out the cc_import for offers, wants, users with balances, and transactions, admin/marketplace/import.
You can also generate random transactions and offers/wants using generate_transaction_node() in transactions.admin.inc

Further set up

Email notifications
Ensure that actions and trigger modules are enabled.
Go to admin/settings/actions and add the advanced action 'Mail anyone who needs to complete a transaction'
Configure the action by writing the email using the wildcards
Go to admin/build/trigger and assign the action to the trigger 'When either saving a new post or updating an existing post'
Note that users can disable their notifications on their user profile pages

Directory Categories
Note that offers/wants created a vocabulary called 'Directory categories' which governs those types.
This is the vocabulary for which you may want to enable autocategorise.

Now you can consider the architecture. 
This module attempts to be usable by default, but it's up to you how users will ultimately experience the site. 
The first level of architecture is in menus and blocks.
For more ideas visit test1.communityforge.net or download the cc_custom module from http://marketplace.matslats.net/installing

Customisation
The site has been designed with classic LETS in mind. Have fun creating your own terminology on admin/marketplace.
Be creative with the transaction certificate transaction.tpl.php
Upload your own a currency icon
Look at the theme functions provided and see if you can do better



***********************
**   ARCHITECTURE    **
***********************
(For html version see http://matslats.net/mutual-credit-architecture-social-network)
Introduction

This architecture supports mutual credit systems such as LETS, SEL, Tauschring and Timebanks. These systems are characterised by the sum and mean of all user balances always being zero. There is no central authority issuing the currency, managing liquidity or inflation. There is no possibility of forgery because the system calculates the balances from the sum total of transactions, and every transaction has a description, a payer and a payee.
The story of a transaction

Ben did some Gardening for Ann. She logs on to her community web site to pay him. On his profile she fills in a form entitled 'transact with Ben' She simply completes the transaction direction (that she is paying him), the number of credits, and a description of what he did. The system infers that she is starting the transaction and that Ben is completing it. Then the system checks that both are within their balance limits before showing a confirmation page which asks her to rate Ben's work. When she submits this she can see her cleared balance has changed and has an opportunity to edit or delete the transaction before Ben signs it.

Ben receives an email and clicks on it. He sees the transaction waiting to be completed on his 'money' page. He clicks to sign it and the transaction is complete and can only be edited by an administrator.
Transaction Form

Another important component is the transaction form, which needs to be context sensitive to save users filling in unneccessary fields. The form has three modes.

    * INIT, for when only one or two fields are provided. This will typically show the starter/completer selectors
    * EDIT, for a user to edit their transaction before the completer signs it. This doesn't allow changing of the participants or the direction because it would be too complex
    * FULLEDIT, for an administrator to have full access. This form allows for contradictory data in the payer/payee/starter/completer selectors.

The form building function takes a transaction object and uses it to prefill fields, which may then be hidden from the user.
The transaction object

At the heart of the system is the transaction object. It contains the following properties:

    * Transaction ID: auto-increment integer (Key)
    * Title: string
    * Quantity: integer or float
    * Currency ID: currency ID
    * Quality rating: float
    * Payer ID: user ID
    * Payee ID: user ID
    * Starter ID: user ID
    * Completer ID: user ID
    * Transaction type: enum
    * Depends On: transaction ID
    * Currency: currency data
    * Starter: information about the starter user
    * Completer: information about the completer user
    * State: enum.

Note that this contains redundant information, but it is stored in this way for ease of access. This may also be true of the database structure as well as the object in memory.

There are four possible transaction types, namely:

    * incoming_direct
    * incoming_confirmed
    * outgoing_direct
    * outgoing_confirmed.

A confirmed transaction is one that will wait for the 'completer' to 'sign' it before it is marked completed. Each of these has a colloquial name which the administrator decides, and if unnamed the transaction will not be known on the system. Each of the named transactions should appear in the permissions. Particularly the incoming_direct should be used with caution.

There are three transaction states so far:

    * completed
    * pending
    * deleted
    * with room for another contested

Whenever the transaction form is processed, or a transaction loaded from the database, it must first be converted to a transaction object. In this format it can be passed around the system. The form will sometimes contain only partial information, such as starter, completer and direction, so the payee and payer need to be derived, or inferred from the other data.
Permissions

These need to be a little more elaborate than many frameworks probably provide. Transactions can always be viewed by an accountant, and either of the participants. But systems will want to decide for themselves whether transactions can be seen by all members or even by non-members. And of course the ability to 'edit' or 'sign' a transaction depends on who it's starter and completer are.
Transaction actions

When a transaction is viewed in a list or on a page of it's own, some buttons are provided, depending on the user's permissions. The buttons are Sign, Edit, Delete. These are nothing to do with the transaction form.
API

This is not well developed, but the most useful function will create a transaction in one function generate_transaction_node($title, $payer_uid, $payee_uid, $quantity, $options=array(), $cid=0){} In the Complementary Currencies module for Drupal, this is used to create the transaction from the form, but also for mass payments and generating example data. It has also been used for auto-payments such as demurrage, and will shortly be used to imprint transactions from offers and wants.
Balances

The system retains it's integrity by deriving the balances from the sum of a user's transactions. So for performance reasons there is a cache table which contains a row for each currency for each user:

    * User ID
    * Currency ID
    * Current balance
    * Cleared balance
    * Pending difference
    * Gross income
    * Average rating

Displays

There are many ways to do this but the suggested displays are:

    * User balances
    * User transactions by month, incoming and outgoing
    * Recent user transactions, showing the running balance
    * A history of the account balance as a google chart
    * A list of all pending transactions

Most of the displays depend on a function which will get all the transactions a user was involved in, and optionally add the running balance, and return the ones for a given time period. I call this
function get_transactions_for_user($uid, $options=array(), $running_balance = FALSE){}
Transaction ratings

A configuration text field invites administrators to determine the scale on which transactions are rated. This is actually asking for numeric keys and textual values for the rating dropdown selector. Ratings are averaged out and presented alongside balances, per currency.
Accounts

In the real world here isn't always a 1:1 relationship between users and accounts. Currently the system assumes account 1 (like user 1 in Drupal) is special. But there is a need for more non-member accounts. Taking this idea further, the trading account numbers could be decoupled from the user IDs and users would have permissions to manage certain accounts, rather than identifying with those accounts.
Stats

There is one basic function which loads all recent transactions and analyses them into a data structure. This can also be cached before being handed to a display function

Reporting.

A cron task has been included in the transactions module which will ping Community Forge. Right now it just collects the domain name, site name, number of active members and number of transactions in the last 30 days.

Classified ads, (Offers and wants)

This is a simple content type which can be edited by its creator and admin only. It has the following properties:

    * title
    * body (optional)
    * price (version 1.1 only)
    * currency ID - integer (version 1.1 only)
    * unit - one off, per hour, per day (version 1.1 only)

It should be possible to filter the ads according to whether they are goods or services (goods will always have a one off price), what category they belong in (such as household, equipment hire, care), and whether they are offers or wants. It is also useful to be able to order them according to the balance of the members, so as to stimulate trading with the people that need it.
Volunteer recognition system

Some would view it as a currency, but there is a kind of volunteering recognition system (currently at version 0.5). The idea is that a committee member will post a volunteer request to do a specific task. A member will pledge to do it, and a committee member will mark it completed. Whereupon the volunteer 'owns' that task and a kudos counter in his profile is incremented.


***********************
* MULTIPLE CURRENCIES *
***********************
In my early work with LETSlink UK, the need for more than one currency was clearly expressed. Either to allow trading of time-based currencies alongside LETS, or to allow subgroups within a larger system. Even before that, when I first contacted Michael Linton five years ago I learned that he had moved way beyond LETS. He has been proposing for some time that anyone should be able to start a mututal credit currency, and that any number of these currencies could be running in parallel.  Economically though, these ideas have never been demonstrated, in part because of lack of software, and in part, I suspect, because not enough people have yet understood them.
So the marketplace module had multiple currencies built in to the transaction engine, and there is a secondary module which defines currency as a nodetype and provides the forms for editing them. 

The transaction object, form and many of the displays all carry with them a currency ID. In a single currency system, this value is set at zero and the default currency properties stored in a system variable. For systems with more currencies, a database table is needed. Each currency has the following values:

    * name - textfield
    * icon - small image
    * purpose - textfield
    * default max credit - positive integer
    * default max debit - negative integer
    * relative value - that's relative to other currencies within a larger schema. this would be used
    * for trading between currencies and transactions between systems
    * division i.e. integers, hundredths or quarters of an hour
    * zero offset - By offsetting zero you can work to counter any stigma which might be attached to keeping a debit balance.

In a large system in which anyone has permission to create a currency, there is a need to restrict the currencies visible to a given user so as not overwhelm them. Marketplace is experimenting with 'universal' and 'meme' currencies. Universal currencies can be seen by all, but meme currencies can only be seen by people who have traded with them. So meme currencies spread around the system as people use it.

So who wants to be the first to demonstrate that the Lintonomics II is viable?

In the future we might build multiple currencies per transaction. This will involve rebuilding the transaction object to include an array of currency ids and quantities to summarise several transactions with the same id.


***********************
** FOR ADVANCED USERS *
***********************

1. The transaction form is very flexible. You can pre-polulate it with an ad hoc transaction object, and make you're own payment blocks. If you want people to sign up to your theatre trip by making a pre-payment, you might do the following in a block entitled 'Register for MacBeth'
<?php 
  $transaction = array(
    'completer_uid' => 1,
    'transaction_type' => outgoing_direct,
    'title' => 'MacBeth pre-registration',
    'quantity'=>6
  );
  print drupal_get_form('transaction_start_form', (object)$transaction, 'hidden');
?>
The form will be pre-populated with these fields, and the user has only to agree (and confirm agree).
Note the final parameter, hidden. This means that the prepopulated fields will not be on the form. This value can also be set to parameter, which means they will appear disabled on the form.
N.B. The four transaction types in this module work better with starter_uid & completer_uid than payee_uid/payer_uid.

2. the transaction API
if you are developing a module like mass_payments, such as a taxation module, or a communal meal module which creates transactions, without using the form provided, then there is an API for that.
//first of all include the admin.inc file where the api funcntion is stored
module_load_include('admin.inc', 'transactions');
//then
generate_transaction_node($title, $payer_uid, $payee_uid, $quantity, $options=array(), $cid=0);
where the options default to:
array('state'=> TRANSACTION_STATE_COMPLETED, 'type'=>'arbitrary_type', 'rating' => '0', 'starter_uid' => 'payee_uid');



***********************
** TESTING PROCEDURE **
***********************

The following procedure is carried out before every point release on Drupal.org.

Install all the modules except cc_currencies and set up as per instructions above, with 2 other users without accountant permissions (here called users 3 and 4).
User 4 should have your own email address.

Transactions
At admin/marketplace/currencies, change default currency, including a new currency icon
Log in as user 3.
Create a outgoing_direct payment to user 4. You should be redirected to user/3/statement
Create a incoming_confirm to user 4. You should be redirected to user/3/pending
Change the transaction you just created.
Check balance chart, pending page, statement
Check the mail you should have received notifying user 4 of a pending transaction
Login as user 4.
Complete the pending transaction
Attempt a new transaction with user 3 which will take you outside the balance limits.

Install cc_currencies
check previous default currency is intact as edited
add a new currency
repeat previous section

Offers and wants
Create an offer and a want
See them on the directory tab in the user profile
See them in the public directory (link in navigation menu)
HOW DO WE CHECK PRINTABLE VIEW WITH MINIMAL SETUP?

Requack
Create a reuest.
At /requests, click complete and choose user 3
at user/3 see the acknowledgement count

Anonymous users
Log out. See if you can see any transaction information, user profiles, or offers and wants
Try going directory to
user/3
directory/recent_offers
transactions

Mass pay
Log in as user 1
At admin/marketplace/mass_payment, make two payments
one from admin to everyone excepting user 4
one from everyone excepting user 4 to admin

Enable the cc_currencies module.
At admin/marketplace/currency Check that the previous default currency is now a node
If you have phpmyadmin check that all cids are <> 0 in cc_transactions and cc_balance_cache
Add a new currency
start a couple of transactions in the new currency
check user/1/statement, user/1/balances, and transactions

Import
go to admin/content/node and delete all transactions
import 2 users with their balances. Check their user pages
Import 2 offers and 2 wants. Check the directory

