Intertrading is the practice of paying 'between' networks/currencies
I say 'between' because nothing actually moves from one mutual credit clearing system to another.
Instead, each system nominates an 'intertrading' account and trades with that instead.
An intertrading server mediates between all the member systems and ensures that every payment into an intertrading account corresponds to an equal and opposite payment FROM an intertrading account in another system.
Thus creating a mutual credit network of mutual credit systems.
Intertrading accounts are subject to balance limits like other accounts, since systems that trade too much in either direction run into liquidity problems.

This 'client' module communicates with an intertrading server using a REST API, documented herein.
There is just one server, clearingcentral.communityforge.net, and a testing server intertesting.communityforge.net
Membership of those networks is at the discretion of Community Forge, and new accounts need to be activated manually.
The server is not open source, for now.

This API is not final and is only used by this module.
A grindingly slow process is underway to specify and build an equivalent API which more softwares will implement
