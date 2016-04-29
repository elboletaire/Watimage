---
layout: page
title: Watimage Testing
image:
  feature: freeparty.jpg
  credit: elboletaire
  creditlink: https://github.com/elboletaire/Watimage/blob/master/examples/files/LICENSE
comments: true
modified: 2016-04-29
---

To run phpunit tests just run phpunit from `Watimage` root path. But first
ensure you have installed composer dependencies; tests need the composer
`autoload.php` file in order to work properly:

~~~bash
composer install
phpunit
# PHPUnit 4.8.6 by Sebastian Bergmann and contributors.
# ...........................................................
# Time: 34.53 seconds, Memory: 80.25Mb
# OK (59 tests, 182 assertions)
~~~

If you do not have phpunit installed system-wide just do:

~~~bash
composer install
./vendor/bin/phpunit
# PHPUnit 4.8.6 by Sebastian Bergmann and contributors.
# ...........................................................
# Time: 34.53 seconds, Memory: 80.25Mb
# OK (59 tests, 182 assertions)
~~~

The `testVignetteMethod` requires a bit more than other methods to finish. For
that reason I've added it to a phpunit `@group` named `slow` to easily skip it:

~~~bash
phpunit --exclude-group slow
# PHPUnit 4.8.6 by Sebastian Bergmann and contributors.
# .........................................................
# Time: 16.18 seconds, Memory: 77.00Mb
# OK (57 tests, 180 assertions)
~~~

You can also skip that and other effect tests using the whole `effects` group:

~~~bash
phpunit --exclude-group effects
# PHPUnit 4.8.6 by Sebastian Bergmann and contributors.
# ..........................................
# Time: 6.95 seconds, Memory: 31.00Mb
# OK (42 tests, 149 assertions)
~~~

"Visual" tests
--------------

Inside `tests/visual` you'll find a script to visually check that all images
are generated properly.

To run them just cd into the visual tests folders and run the `run_them_all.php` script:

~~~bash
composer install
cd tests/visual
php run_them_all.php
~~~

It will generate a bunch of files in `tests/visual/results` where you can check
(by yourself) if everything is running as expected.

[← Go back to Watimage Usage]({{ site.url }}/usage)

{% include about-image/freeparty.md %}
