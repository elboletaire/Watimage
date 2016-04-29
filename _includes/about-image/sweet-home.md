{% include about-image/header.md %}

~~~php?start_inline=1
$image = new Image('sweet-home-original.jpg');
$image
    ->resize('resizeCrop', 1920, 600)
    ->brightness(-10)
    ->generate()
;

~~~

{% include about-image/footer.md %}
