Branching and Versioning
=========================

## Branching
From ILIAS 8 onwards, it will no longer be possible for a plugin to support several ILIAS versions. Therefore, we introduce that one OpenCast plugin has several branches, each with its target version of ILIAS.

The previous branch `main` must be replaced and a separate branch must be maintained for each ILIAS version(s). This has a major impact on further development and bug fixing. These are explained below.

In general, we will have the following branches in the future:

- release_X: Version which is compatible for a certain ILIAS version

Branches in forks to make pull requests should follow the following naming if possible:

- fix/X/name-of-the-fixes
- feature/X/name-of-features

The main branch is no longer needed and is deleted. Features or fixes are implemented in corresponding branches and thus placed as a pull request on the respective target release branch; after acceptance, these are merged directly into the respective release branches (pull request). If possible, features are only developed for the latest release branch, but if desired, the feature must also be developed in older release branches and then picked in the newer release branches. Fixes are always picked in all release branches that are still supported&ast;.

&ast; The supported versions are derived from the ILIAS versions that are still supported.

The change-over takes place with the conversion of a branch for ILIAS 8. In doing so, the previous `main` branch is converted into `release_7`, as this is the last supported ILIAS version of the main-branch.

## Versioning
We use semantic versioning as a basis for the versioning of plug-ins, see https://semver.org/lang/de/.

In concrete terms this means:

- Bug fixes (also several combined): Maintenance version is upgraded, e.g. from 2.1.9 to 2.1.10.
- Minor features (also several combined): Minor version is upgraded, e.g. from 2.1.10 to 2.2.0.
- As soon as the plugin supports a new ILIAS version: Major release is upgraded, e.g. from 2.2.13 to 3.0.0.

If, for example, a major is published, the minor and the patch version are set to 0. For example 1.1.4 -> 2.0.0 or 1.1.4 -> 1.2.0.

If a new release was made, we tag the commit with the corresponding version, each with a preceding "v" (v1.1.4 or v5.0.0), tagging lighweight (without annotation):

```bash
$ git tag v6.1.0
```

This ensures that the new branching (see above) cannot result in two identical verison numbers.
