Treat Javascript objects in Firefox as if they were PHP objects.

# Usage

```php
<?php
// configuration
$_ENV['PDR_MOZREPL_HOST'] = '127.0.0.1';
$_ENV['PDR_MOZREPL_PORT'] = 4242;

$window = new \Pdr\MozRepl\Window;

// navigate to an url
$window->navigate('http://localhost/');

// fill username
$window->document->querySelector('#u')->value = 'username';
// fill password
$window->document->querySelector('#p')->value = 'password';
// click login button
$window->document->querySelector('input[name=in]')->click();

// wait until page loaded
$window->waitReady();
```

# Installation

 1. Download [mozrepl.xpi](docs/mozrepl.xpi)
 2. Install MozRepl: open firefox, navigate to File > Open and choose mozrepl.xpi
 3. Start MozRepl: Tools > MozRepl > Start
