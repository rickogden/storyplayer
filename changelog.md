---
layout: top-level
title: ChangeLog
prev: '<a href="modules/zeromq/usingZmq.html">Prev: usingZmq()</a>'
next: '<a href="copyright.html">Next: Legal Stuff</a>'
---

# ChangeLog

## v1.5.0

Currently under development.

## v1.4.0

Released 6th September 2013.

### New

* `-D` switch for passing params into stories ([#37](https://github.com/datasift/storyplayer/pull/37))
* better sub-process handling ([#46](https://github.com/datasift/storyplayer/pull/46))
* create-story command ([#47](https://github.com/datasift/storyplayer/pull/47))
* [Firefox and Safari support](devices/localwebbrowsers.html) ([#45](https://github.com/datasift/storyplayer/pull/45))
* [Prose modules can have their own namespaces](prose/module-namespaces.html) ([#55](https://github.com/datasift/storyplayer/pull/55))
* [Remote WebDriver](devices/remotewebdriver.html) for advanced browser / device testing ([#62](https://github.com/datasift/storyplayer/pull/62))
* [Resize current browser window](modules/browser/usingBrowser.html#resizecurrentwindow) ([#52](https://github.com/datasift/storyplayer/pull/52))
* [Sauce Labs integration](devices/saucelabs.html) for cross-browser / cross-device testing ([#57](https://github.com/datasift/storyplayer/pull/57))
* [Simplified module loading](prose/module-loading.html) ([#56](https://github.com/datasift/storyplayer/pull/56))
* works without any config files ([#40](https://github.com/datasift/storyplayer/pull/40))

### Fixed

* Browser module: drop the on-screen / off-screen check ([#53](https://github.com/datasift/storyplayer/pull/53))
* usingBrowser()->getValue() now works with input fields too ([#54](https://github.com/datasift/storyplayer/pull/54))
* usingShell()->runCommand() now returns all lines of output, not just the last one ([#58](https://github.com/datasift/storyplayer/pull/58))