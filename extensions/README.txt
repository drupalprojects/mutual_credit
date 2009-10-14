The modules in this directory are all optional, and when the time is right, may be moved out of the mutual_credit package

cc_currencies
In my early work with LETSlink UK, the need for more than one currency was clearly expressed. Either to allow trading of time-based currencies alongside LETS, or to allow subgroups within a larger system. Even before that, when I first contacted Michael Linton five years ago I learned that he had moved way beyond LETS. He has been proposing for some time that anyone should be able to start a mututal credit currency, and that any number of these currencies could be running in parallel.  Economically though, these ideas have never been demonstrated, in part because of lack of software, and in part, I suspect, because not enough people have yet understood them.
So the marketplace module had multiple currencies built in to the transaction engine, and there is a secondary module which defines currency as a nodetype and provides the forms for editing them. So who wants to be the first to demonstrate that the Lintonomics II is viable?

cc_import
Should probably be used in conjunction with user_import module. After users are imported you can import balances, gross income, offers and wants.

cc_mass_pay
Two forms which allow payments between one account and many, in each direction

cc_offer_want
A content type and vocabulary designed for making a directory of offers and/or wants, with categories. Several views are included