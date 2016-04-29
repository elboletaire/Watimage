---
layout: page
title: Watimage Normalize Class Usage
image:
  feature: sweet-home.jpg
  credit: elboletaire
  creditlink: https://github.com/elboletaire/Watimage/blob/master/examples/files/LICENSE
comments: false
modified: 2016-04-29
---

The normalize class is a static helper class that normalizes all the parameters
passed to any Watimage class. Something probably useless for you.

Interesting methods where you could take proffit:

- `color`: bring any color as hex or as an array and will return you an array
  with the `r`, `g` and `b` values.

See [the tests](https://github.com/elboletaire/Watimage/blob/5f8736f82a/tests/TestCase/NormalizeTest.php#L8)
for more details.

[← Go back to Watimage Classes Usage]({{ site.url }}/usage/classes)

{% include about-image/sweet-home.md %}
