{% include about-image/header.md %}

~~~php
$image = new Image('el-carmel-viewpoint.jpg');
$image
  ->contrast(-5)
  ->brightness(-60)
  ->colorize(['r' => 100, 'g' => 70, 'b' => 50, 'a' => 0])
  ->brightness(-30)
  ->contrast(-5)
  ->generate()
;
~~~

{% include about-image/footer.md %}
