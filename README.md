# CIViews

A Simple, Native PHP Templating Engine decoupled from CodeIgniter 4

## Usage

**Writing Templates**: [refer to CI4 docs](https://codeigniter.com/user_guide/outgoing/views.html)

### Example 1: 

```php
$data = [
  'title' => 'Dummy Title'
];

$renderer = new \SimiplyDi\CIViews\Renderer('/path/to/templates/dir');
$renderer->data = $data; // or $renderer->setVar('title', $data['title']);
echo $renderer->render('home');
```

### Example with DI

**MyController.php**:

```php
class MyController
{

    private RendererInterface $renderer;

    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }

    public function index(): string
    {
        $this->renderer->data = [
            'title' => 'Welcome to website',
            'content' => 'welcome to website',
        ];

        return $this->renderer->render('home');
    }
}
```

**Dependency Container** (use any container you want). Example:

```php

$container = new Container();

$container->bind(RendererInterface::class, function () {
    // pass the templates directory as first param and extension you want to use (optional; defaults to .php)
    return new Renderer(__DIR__ . '/views', '.phtml');
});

$container->bind(MyController::class, function () use ($container) {
    return new MyController($container->resolve(RendererInterface::class));
});

```
