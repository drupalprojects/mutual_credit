Translator's guidelines

you will need a program like poedit
http://www.poedit.net/
This may take up to a day.

Edit a language, open the .po file in poedit
To create a new language, open the .pot file in poedit and save as a po file

For convenience, all the strings in all the enclosed module are in one file.

===============
What it all means.
This module provides forms for users to record their exchanges. It stores the exchanges, displays the summary information and provides various configuration options for administrators.

An exchange consists of a payer and a payee who exchange a something and record it with a quantity of a currency.

It is possible to make many currencies on the system, each with it's own properties such as its balance limits, its icon, its rating scale, and others. Each currency can have different subdivisions, such as centiles or user-defined divisions.

A balance is the total amount of a currency in a user account. Other balance-related information is stored such as the amount of available credit a user has, (and it's inverse). individual balance limits can be set for users which over-ride the currency's balances.

The statistics mostly record the most prolific exchangers, by various measures. They also count the trades accross the system.

The 'usable' or 'user-friendly' form, does not ask for the payer and payee in an exchange. Rather it asks for the Other participant and the type of exchange.

Exchanges may have 'signatories' which means they are 'pending' until all the signatories have 'signed'. Pending exchanges are not counted in stats or balances.

===============
Please contribute back your translations and/or put them on localize.drupal.org