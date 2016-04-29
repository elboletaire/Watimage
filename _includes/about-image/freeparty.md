{% include about-image/header.md %}

~~~php?start_inline=1
$image = new Image('freeparty-original.jpg');
$image
    ->resize('crop', 1920, 500)
    ->generate()
;
~~~

{% include about-image/footer.md %}
