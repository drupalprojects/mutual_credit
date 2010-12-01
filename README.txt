This document is 4 documents in one:

** Features **
** How to set up the Complementary currencies package **
** Generalised architecture of this mutual credit system **
** Testing procedure **

***********************
**     FEATURES      **
***********************

Switch between floating point and integer db storage.


***********************
**   HOW TO SET UP   **
***********************

ENABLE MODULES
Enable exchanges module, views
Optionally enable the other modules in the Complementary Currencies Section.
PHP_filter will enables default user friendly links at the bottom of some views, which you could easily change
Also consider uid_login and autocategorise, and user_tabs

exchanges
Setup your currency and other options at admin/marketplace and the pages under it.

Permissions
Don't forget to define the permissions AFTER naming the exchange-types for these modules at admin/user/permissions.
Owing to technical limitations, all trading users must have permission to 'view all exchanges'

Complementary Currencies should now function. However before your site makes sense, you'll need to add some users and data.
Check out the mc_import for offers, wants, users with balances, and exchanges, admin/marketplace/import.
You can also generate random exchanges and offers/wants using generate_exchange_node() in exchanges.admin.inc

Further set up

Email notifications
Ensure that actions and trigger modules are enabled.
Go to admin/settings/actions and add the advanced action 'Mail anyone who needs to complete a exchange'
Configure the action by writing the email using the wildcards
Go to admin/build/trigger and assign the action to the trigger 'When either saving a new post or updating an existing post'
Note that users can disable their notifications on their user profile pages

Directory Categories
Note that offers/wants created a vocabulary called 'Directory categories' which governs those types.
This is the vocabulary for which you may want to enable autocategorise.

Now you can consider the architecture. 
This module attempts to be usable by default, but it's up to you how users will ultimately experience the site. 
The first level of architecture is in menus and blocks.
For more ideas visit test1.communityforge.net or download the mc_custom module from http://marketplace.matslats.net/installing

Customisation
The site has been designed with classic LETS in mind. Have fun creating your own terminology on admin/marketplace.
Be creative with the exchange certificate exchange.tpl.php
Upload your own a currency icon
Look at the theme functions provided and see if you can do better



***********************
**   ARCHITECTURE    **
***********************
(For html version see http://matslats.net/mutual-credit-architecture-social-network)
Introduction

This architecture supports mutual credit systems such as LETS, SEL, Tauschring and Timebanks. These systems are characterised by the sum and mean of all user balances always being zero. There is no central authority issuing the currency, managing liquidity or inflation. There is no possibility of forgery because the system calculates the balances from the sum total of exchanges, and every exchange has a description, a payer and a payee.
The story of a exchange

Ben did some Gardening for Ann. She logs on to her community web site to pay him. On his profile she fills in a form entitled 'transact with Ben' She simply completes the exchange direction (that she is paying him), the number of credits, and a description of what he did. The system infers that she is starting the exchange and that Ben is completing it. Then the system checks that both are within their balance limits before showing a confirmation page which asks her to rate Ben's work. When she submits this she can see her cleared balance has changed and has an opportunity to edit or delete the exchange before Ben signs it.

Ben receives an email and clicks on it. He sees the exchange waiting to be completed on his 'money' page. He clicks to sign it and the exchange is complete and can only be edited by an administrator.
exchange Form

The mutual_credit_api provides a new 'exchange' node-type: and caching facility which stores per user trading statistics, including the balances
It also declares a currencies node type, though most use cases require only one currency.

Depending on that there is the mc_webforms module Instead of using the naked node/add/transaction facility, this module contains 4 exchange forms for different purposes
It also introduces exchanges in a 'pending' state and provodes a form for the completer to confirm and a list of pending exchanges for each user.

The mcapi_user module creates a 'bureau' page which is the default home for some themable user data

Permissions
Permissions for transaction types, transaction states and currencies would make a three dimensionsl grid, so permissions are optimised around a single currency.

API

modules wanting to create transaction nodes, should use this function
generate_exchange_node($title, $payer_uid, $payee_uid, $quantity, $cid, $options = array())
For a list of default $options, see the function in mcapi.inc

The system retains it's integrity by deriving the balances from the sum of users' exchanges.
So for performance reasons there is a cache table which contains a row for each currency for each user:

    * User ID
    * Currency ID
    * Balance
    * Gross income
    * Gross expenditure

Displays

There are many ways to do this but the suggested displays are:

    * User balances
    * User exchanges by month, incoming and outgoing
    * Recent user exchanges, showing the running balance
    * A history of the account balance as a google chart
    * A list of all pending exchanges

Most of the displays depend on a function which will get all the exchanges a user was involved in, and optionally add the running balance, and return the ones for a given time period. I call this
function get_exchanges_for_user($uid, $options=array(), $running_balance = FALSE){}
exchange ratings

A configuration text field invites administrators to determine the scale on which exchanges are rated. This is actually asking for numeric keys and textual values for the rating dropdown selector. Ratings are averaged out and presented alongside balances, per currency.
Accounts

There isn't always a 1:1 relationship between users and accounts. Currently the system assumes account 1 (like user 1 in Drupal) is special. But there is a need for more non-member accounts. Taking this idea further, the trading account numbers could be decoupled from the user IDs and users would have permissions to manage certain accounts, rather than identifying with those accounts.
Stats

There is one basic function which loads all recent exchanges and analyses them into a data structure. This can also be cached before being handed to a display function
Currencies and multiple currencies

So the exchange object, form and many of the displays all carry with them a currency ID. In a single currency system, this value is set at zero and the default currency properties stored in a system variable. For systems with more currencies, a database table is needed. Each currency has the following values:

    * name - textfield
    * icon - small image
    * purpose - textfield
    * default max credit - positive integer
    * default max debit - negative integer
    * relative value - that's relative to other currencies within a larger schema. this would be used
    * for trading between currencies and exchanges between systems
    * division i.e. integers, hundredths or quarters of an hour
    * zero offset - By offsetting zero you can work to counter any stigma which might be attached to keeping a debit balance.

In a large system in which anyone has permission to create a currency, there is a need to restrict the currencies visible to a given user so as not overwhelm them. Marketplace is experimenting with 'universal' and 'meme' currencies. Universal currencies can be seen by all, but meme currencies can only be seen by people who have traded with them. So meme currencies spread around the system as people use it.

In the future we might build multiple currencies per exchange. This will involve rebuilding the exchange object to include an array of currency ids and quantities to summarise several exchanges with the same id.
Reporting.

Because of the lack of coordination in the CC movement, this function does a little data gathering. Right now it just collects the domain name, site name, number of active members and number of exchanges in the last 30 days.
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

Making your own forms

1. The exchange form is very flexible. You can pre-polulate it with an ad hoc exchange object, and make you're own payment blocks. If you want people to sign up to your theatre trip by making a pre-payment, you might do the following in a block entitled 'Register for MacBeth'
<?php 
  $exchange = array(
    'completer_uid' => 1,
    'exchange_type' => outgoing_direct,
    'title' => 'MacBeth pre-registration',
    'quantity'=>6
  );
  print drupal_get_form('exchange_start_node_form', (object)$exchange, 'hidden');
?>
The form will be pre-populated with these fields, and the user has only to agree (and confirm agree).
Note the final parameter, hidden. This means that the prepopulated fields will not be on the form. This value can also be set to parameter, which means they will appear disabled on the form.
N.B. The four exchange types in this module work better with starter_uid & completer_uid than payee_uid/payer_uid.


***********************
** TESTING PROCEDURE **
***********************

The following procedure is carried out before every point release on Drupal.org.

Install all the modules except mc_currencies and set up as per instructions above, with 2 other users without accountant permissions (here called users 3 and 4).
User 4 should have your own email address.

exchanges
At admin/marketplace/currencies, change default currency, including a new currency icon
Log in as user 3.
Create a outgoing_direct payment to user 4. You should be redirected to user/3/statement
Create a incoming_confirm to user 4. You should be redirected to user/3/pending
Change the exchange you just created.
Check balance chart, pending page, statement
Check the mail you should have received notifying user 4 of a pending exchange
Login as user 4.
Complete the pending exchange
Attempt a new exchange with user 3 which will take you outside the balance limits.

Install mc_currencies
check previous default currency is intact as edited
add a new currency
repeat previous section

Offers and wants
Create an offer and a want
See them on the directory tab in the user profile
See them in the public directory (link in navigation menu)

Anonymous users
Log out. See if you can see any exchange information, user profiles, or offers and wants
Try going directory to
user/3
directory/recent_offers
exchanges

Mass pay
Log in as user 1
At admin/marketplace/mass_payment, make two payments
one from admin to everyone excepting user 4
one from everyone excepting user 4 to admin

Enable the mc_currencies module.
At admin/marketplace/currency Check that the previous default currency is now a node
If you have phpmyadmin check that all cids are <> 0 in mc_exchanges and mc_balance_cache
Add a new currency
start a couple of exchanges in the new currency
check user/1/statement, user/1/balances, and exchanges

Import
go to admin/content/node and delete all exchanges
import 2 users with their balances. Check their user pages
Import 2 offers and 2 wants. Check the directory

