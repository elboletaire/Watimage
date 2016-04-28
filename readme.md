Watimage jekyll pages
=====================

Running locally
---------------

First you need RVM installed, after that do:

~~~bash
rvm use 2.3
gem install bundler
bundle install
~~~

Ensure that `_config.yaml` has proper host configuration (under `url`) and,
after that:

~~~bash
jekyll build
jekyll serve
~~~
