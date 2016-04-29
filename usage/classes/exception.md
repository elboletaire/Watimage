---
layout: page
title: Watimage Exceptions Usage
image:
  feature: freeparty-back-vintage.jpg
  credit: elboletaire
  creditlink: https://github.com/elboletaire/Watimage/blob/master/examples/files/LICENSE
comments: true
modified: 2016-04-29
---

Watimage uses 5 custom exception classes that extend from php's `Exception`.

- ExtensionNotLoadedException
- FileNotExistException
- InvalidArgumentException
- InvalidExtensionException
- InvalidMimeException

Knowing that, you can catch exceptions one by one:

~~~php?start_inline=1
use Elboletaire\Watimage\Exception\ExtensionNotLoadedException;
use Elboletaire\Watimage\Exception\FileNotExistException;
use Elboletaire\Watimage\Exception\InvalidArgumentException;
use Elboletaire\Watimage\Exception\InvalidExtensionException;
use Elboletaire\Watimage\Exception\InvalidMimeException;

try {

} catch (ExtensionNotLoadedException $e) {
} catch (FileNotExistException $e) {
} catch (InvalidArgumentException $e) {
} catch (InvalidExtensionException $e) {
} catch (InvalidMimeException $e) {
}
~~~

Or just catch from `Exception`:

~~~php?start_inline=1
use Exception;

try {

} catch (Exception $e) {
}
~~~

[← Go back to Watimage Classes Usage]({{ site.url }}/usage/classes)

{% include about-image/freeparty-vintage.md %}
