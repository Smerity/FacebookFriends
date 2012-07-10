Facebook Friends
================

This is a plugin for [Vanilla Forums](http://vanillaforums.org/) that gives Facebook users the ability to see the real name of anyone they're friends with.

![Example: Superman reveals his identity](https://github.com/Smerity/FacebookFriends/raw/master/example.png "Superman reveals his identity")

Important Notes
---------------

+ The Facebook plugin needs to be enabled and this will only work for users who have logged on via Facebook
+ For good performance, you'll need to enable some form of caching (preferably Memcache) as otherwise each page load requires a query to Facebook via their API
