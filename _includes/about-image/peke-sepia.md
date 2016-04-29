{% include about-image/header.md %}

~~~php?start_inline=1
$image = new Image('peke.jpg');
$image
    ->sepia(60)
    ->resizeCrop(1100, 400)
    ->vignette(.3)
    ->generate()
;
~~~

{% include about-image/footer.md %}
