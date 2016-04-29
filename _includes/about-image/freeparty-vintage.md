{% include about-image/header.md %}

~~~php?start_inline=1
$image = new Image('freeparty-back.jpg');
$image
    ->contrast(-5)
    ->brightness(-60)
    ->colorize(['r' => 100, 'g' => 70, 'b' => 50, 'a' => 0])
    ->brightness(-35)
    ->contrast(-5)
    ->colorize(['r' => 0, 'g' => 5, 'b' => 15, 'a' => 0])
    ->vignette(.1)
    ->generate()
;
~~~

{% include about-image/footer.md %}
