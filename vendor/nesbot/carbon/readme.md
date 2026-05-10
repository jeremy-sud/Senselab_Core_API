# Carbon

[![Latest Stable Version](https://img.shields.io/packagist/v/nesbot/carbon.svg?style=flat-square)](https://packagist.org/packages/nesbot/carbon) [![Total Downloads](https://img.shields.io/packagist/dt/nesbot/carbon.svg?style=flat-square)](https://packagist.org/packages/nesbot/carbon) [![GitHub Actions](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Factions-badge.atrox.dev%2FCarbonPHP%2Fcarbon%2Fbadge\&style=flat-square\&label=Build\&logo=none)](https://github.com/CarbonPHP/carbon/actions) [![codecov.io](https://img.shields.io/codecov/c/github/CarbonPHP/carbon.svg?style=flat-square)](https://codecov.io/github/CarbonPHP/carbon/actions?branch=master)

An international PHP extension for DateTime. [https://carbon.nesbot.com](https://carbon.nesbot.com)

> \[!NOTE]\
> We're migrating the repository from [briannesbitt/Carbon](https://github.com/briannesbitt/Carbon) to [CarbonPHP/carbon](https://github.com/CarbonPHP/carbon), which means if you're looking specific issues/pull-requests, you may have to search both. No other impact as code on both will be kept up to date.

```php
<?php

use Carbon\Carbon;

printf("Right now is %s", Carbon::now()->toDateTimeString());
printf("Right now in Vancouver is %s", Carbon::now('America/Vancouver'));  //implicit __toString()
$tomorrow = Carbon::now()->addDay();
$lastWeek = Carbon::now()->subWeek();

$officialDate = Carbon::now()->toRfc2822String();

$howOldAmI = Carbon::createFromDate(1975, 5, 21)->age;

$noonTodayLondonTime = Carbon::createFromTime(12, 0, 0, 'Europe/London');

$internetWillBlowUpOn = Carbon::create(2038, 01, 19, 3, 14, 7, 'GMT');

// Don't really want this to happen so mock now
Carbon::setTestNow(Carbon::createFromDate(2000, 1, 1));

// comparisons are always done in UTC
if (Carbon::now()->gte($internetWillBlowUpOn)) {
    die();
}

// Phew! Return to normal behaviour
Carbon::setTestNow();

if (Carbon::now()->isWeekend()) {
    echo 'Party!';
}
// Over 200 languages (and over 500 regional variants) supported:
echo Carbon::now()->subMinutes(2)->diffForHumans(); // '2 minutes ago'
echo Carbon::now()->subMinutes(2)->locale('zh_CN')->diffForHumans(); // '2分钟前'
echo Carbon::parse('2019-07-23 14:51')->isoFormat('LLLL'); // 'Tuesday, July 23, 2019 2:51 PM'
echo Carbon::parse('2019-07-23 14:51')->locale('fr_FR')->isoFormat('LLLL'); // 'mardi 23 juillet 2019 14:51'

// ... but also does 'from now', 'after' and 'before'
// rolling up to seconds, minutes, hours, days, months, years

$daysSinceEpoch = Carbon::createFromTimestamp(0)->diffInDays(); // something such as:
                                                                // 19817.6771
$daysUntilInternetBlowUp = $internetWillBlowUpOn->diffInDays(); // Negative value since it's in the future:
                                                                // -5037.4560

// Without parameter, difference is calculated from now, but doing $a->diff($b)
// it will count time from $a to $b.
Carbon::createFromTimestamp(0)->diffInDays($internetWillBlowUpOn); // 24855.1348
```

## Installation

### With Composer

```
$ composer require nesbot/carbon
```

```json
{
    "require": {
        "nesbot/carbon": "^3"
    }
}
```

```php
<?php
require 'vendor/autoload.php';

use Carbon\Carbon;

printf("Now: %s", Carbon::now());
```

### Without Composer

Why are you not using [composer](https://getcomposer.org/)? Download the Carbon [latest release](https://github.com/CarbonPHP/carbon/releases) and put the contents of the ZIP archive into a directory in your project. Then require the file `autoload.php` to get all classes and dependencies loaded on need.

```php
<?php
require 'path-to-Carbon-directory/autoload.php';

use Carbon\Carbon;

printf("Now: %s", Carbon::now());
```

## Documentation

[https://carbon.nesbot.com/docs](https://carbon.nesbot.com/docs)

## Security contact information

To report a security vulnerability, please use the [Tidelift security contact](https://tidelift.com/security). Tidelift will coordinate the fix and disclosure.

## Credits

### Contributors

This project exists thanks to all the people who contribute.

[![](https://opencollective.com/Carbon/contributors.svg?width=890\&button=false)](https://github.com/CarbonPHP/carbon/graphs/contributors)

### Translators

[Thanks to people helping us to translate Carbon in so many languages](https://carbon.nesbot.com/contribute/translators/)

### Sponsors

Support this project by becoming a sponsor. Your logo will show up here with a link to your website.

&#x20;        &#x20;

<details>

<summary>See more</summary>

&#x20;                        &#x20;

</details>

\[[See all](https://carbon.nesbot.com/#sponsors)]

\[[Become a sponsor via OpenCollective\*](https://opencollective.com/Carbon#sponsor)]

\[[Become a sponsor via GitHub\*](https://github.com/sponsors/kylekatarnls)]

\* This is a donation. No goods or services are expected in return. Any requests for refunds for those purposes will be rejected.

### Backers

Thank you to all our backers! 🙏

[![](https://opencollective.com/Carbon/backers.svg?width=890\&version=2023-06-08-07-12)](https://opencollective.com/Carbon#backers)

\[[Become a backer](https://opencollective.com/Carbon#backer)]

## Carbon for enterprise

Available as part of the Tidelift Subscription.

The maintainers of `Carbon` and thousands of other packages are working with Tidelift to deliver commercial support and maintenance for the open source dependencies you use to build your applications. Save time, reduce risk, and improve code health, while paying the maintainers of the exact dependencies you use. [Learn more.](https://tidelift.com/subscription/pkg/packagist-nesbot-carbon?utm_source=packagist-nesbot-carbon\&utm_medium=referral\&utm_campaign=enterprise\&utm_term=repo)
