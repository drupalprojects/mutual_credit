The limits module extends the currency definition with balance limits.
A transaction cluster will fail validation if it would take any of the participants beyond their balance limits in any currency.
Balance limits are not stored, but always calculated on the fly.
Balance limits can be shown in two ways, absolute, which is the min and max values, and relative, which is the earning and spending limits in relation to the currency balance
E.g. so if the balance limits are +- 100 and your balance is +90 you relative limits will be: earning limit 10, spending limit 190
Four plugins are provided for calculating the balance limits.
 - None, the default
 - Calculated allows you to put in a formula
 - Explicit means you just enter the max and min into the currency
 - Balanced means that the min is the max * -1