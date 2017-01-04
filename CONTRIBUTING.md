# Contributing to Disqontrol

Contributions are welcome.

We accept contributions via pull requests on
[Github](https://github.com/disqontrol/disqontrol) and merge requests on
[Gitlab](https://gitlab.com/disqontrol/disqontrol).

## Pull Requests

- **Follow the [PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)** -
The easiest way to apply the conventions is to install
[PHP Code Sniffer](http://pear.php.net/package/PHP_CodeSniffer).

- **Add tests** - If you change or add new logic, write tests for it.

- **Document any change in behaviour** - Make sure the `README.md`,
`CHANGELOG.md`, examples in `docs/examples` and any other relevant
documentation are kept up-to-date.

- **Consider our release cycle** - We follow [semantic versioning](http://semver.org/).
Do not change public API.

- **Create feature branches** - Don't ask us to pull from your master branch.

- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

- **Send coherent history** - Make sure each individual commit in your pull request is meaningful and working.
If you had to make multiple intermediate commits while developing, please
[squash them](http://www.git-scm.com/book/en/v2/Git-Tools-Rewriting-History#Changing-Multiple-Commit-Messages) 
before submitting.

- **Format commits appropriately** - Start with a verb
in imperative form (Fix, Add, Improve, Refactor), write a summary on the first
line, separated from an optional longer description by a blank line.
Limit lines to 80 characters.

- **Document the code** - Write docblocks and add extra comments where your
intention might not be clear from the code and the names inside.

## Running Tests

``` bash
$ vendor/bin/phpunit
```
