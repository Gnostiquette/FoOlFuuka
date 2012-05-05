Choosing the server
###################

Servers come most frequently in 3 flavors:

-  Shared (*not compatible*)
-  VPS (*suggested*)
-  Dedicated server

You can't use shared servers with FoOlFuuka because we've used triggers, which
need you to have full control of your database. Hardly any shared host supports
triggers. The bonus is that you get a faster and reliable database.

General suggestions
*******************

-  Pay your server month by month

   Server companies disappear, kick you off, get worse, leave you with a
   non-functioning server for weeks. You want to be able to leave at any time,
   so don’t commit yourself to year-long subscriptions.

-  Follow the Facebook and Twitter of your server company

   You might get special discounts, and often the guys who are behind the
   social network are nice and willing to help (and not always for show!).

-  Make sure there’s no extra fees for extra service

   Fees for reinstalling the OS, or for any kind of basic support related to
   their server malfunctioning, means that the company is not to be trusted. It
   is instead normal to have costs for bandwidth use overage, and you should
   always keep an eye on the server statistics not to risk having to pay extra.

-  VAT may be exepted for Europeans if the server is in Europe

   Check their page or contact them for more info if the company is European.
   In this guide we’ll point out which servers don’t make you pay the VAT.

Shared
======

*Shared servers are currently not compatible with FoOlFuuka*.

VPS (4-40$/month)
=================

A Virtual Private Server runs on a dedicated server that stores many other VPS.
This allows buying a smaller server for cheaper fees, and to you it will look
exactly as if you had bought an entire server.

Considering the average size of a "large" imageboard, it's relatively safe to
say that a VPS will suffice unless you have one of the largest imageboard
communities on the Internet. One million post, a fairly high target, will be
dealt by FoOlFuuka without issues even on a 512MB RAM VPS. For your
convenience, in this guide we've included setup suggestions for VPSs with 512MB
RAM.

Running an archive on a VPS is highly discouraged, as you will easily reach many
millions of posts and fill up your RAM with the fetcher alone.

Two A-level suggestions. The links are referrals so we save some money on our
servers, but we can guarantee you will not be disappointed.

-  `FanaticalVPS`_ $10+ (*suggested*)

   Small and personal company. Great support, good servers, rare downtimes of
   a few minutes. We love the guys over at Fanatical and they are incredibly
   fast at answering our questions. You need to ask them to enable DNS ports
   via email when you sign up for a new server, but they’re cool with it. You
   must not be afraid to keep in contact with them. Their “unlimited bandwidth”
   is pretty high, which means 500gb~ for the smallest plan.

-  `Linode`_ $20+

   Truly premium servers. The issue is the bandwidth, just 100gb per 10$ you
   spend, but expect great speed and a lot of freedom through their custom
   admin panel. The fastest support we’ve ever been through, and you can run
   basically anything, limited only by the RAM. With a 40$ server you’re going
   really far, without downtimes, ever. Upload to server is free, download
   overage is 0.10$/GB.

Dedicated Servers (60$+)
========================

Dedicated Servers are easily several times more powerful than a VPS, and will
ensure that you won't be kicked off even if you hit some usage limit, because
there's only you on the server and it's your problem if you are stressing that
CPU or the hard disk.

With a dedicated server with 8GB+ RAM you can move the biggest archive in
existence (ours!), but even with less you won’t be shy of hosting tens of
millions of posts. We believe with 16GB of RAM you could keep up with
500.000.000 posts without performance issues. In this guide we've included
setup suggestions for a dedicated server with 16GB of RAM.

Welcome to the highway of imageboards!

-  `Kimsufi`_ 60-80$ (+VAT for Europeans)

   Ignore the Atom server. Kimsufi are OVH’s cheapest server offers, and you
   can get away with really powerful machines, at a cost: support. Kimsufi has
   slow support, possibly slower than Virpus. If your server disappears, get
   ready for hours of wait. Yet, for such a price, a whole i5 server with 2Tb
   of HD, 16GB of RAM and 10TB of bandwidth… with 1$ per extra TB is insane.

-  `OVH`_ 80$+ (+VAT for Europeans)

   Like the above, but with more powerful servers, more choice, and with
   decent support. If you have money to waste, you should absolutely go with
   OVH instead of Kimsufi. If you are at this point, you probably own one of
   the biggest imageboards on Earth, and congratulations, of course.

.. _Virpus.com: http://myvirpus.com/aff.php?aff=535
.. _FanaticalVPS: https://client.fanaticaldev.com/aff.php?aff=215
.. _Linode: http://www.linode.com/?r=14a9f753496f4a13247f6e7c53ab454e68f9c959
.. _Kimsufi: http://kimsufi.ie
.. _OVH: http://ovh.ie

