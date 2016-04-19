{% include about-image/header.md %}

~~~php
$image = new Image('peke.jpg');
$image
    ->sepia(60)
    ->vignette(.3)
    ->generate()
;
~~~

{% include about-image/footer.md %}
