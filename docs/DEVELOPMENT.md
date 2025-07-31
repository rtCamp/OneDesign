# Contributing to the OneDesign as a Developer

Code contributions, bug reports, and feature requests are welcome! The following sections provide guidelines for contributing to this project, as well as information about development processes and testing.

## Table of Contents

- [Contributing to the OneDesign as a Developer](#contributing-to-the-onedesign-as-a-developer)
  - [Table of Contents](#table-of-contents)
  - [Directory Structure](#directory-structure)
  - [Local setup](#local-setup)
    - [Prerequisites](#prerequisites)
    - [Building OneDesign Packages](#building-onedesign-packages)
  - [Code Contributions (Pull Requests)](#code-contributions-pull-requests)
    - [Workflow](#workflow)
    - [Code Quality / Code Standards](#code-quality--code-standards)
      - [ESLint](#eslint)
  - [Changesets](#changesets)
  - [Releasing](#releasing)
    - [Release Commands](#release-commands)

## Directory Structure

<details>
<summary> Click to expand </summary>

```bash
.
├── assets
│   └── src
│       ├── css
│       │   └── editor.scss
│       ├── js
│       │   ├── admin.js
│       │   ├── editor.js
│       │   └── main.js
│       ├── patterns-popover
│       │   ├── components
│       │   │   ├── app.js
│       │   │   ├── AppliedPatternsTab.js
│       │   │   ├── BasePatternsTab.js
│       │   │   ├── Category.js
│       │   │   ├── LibraryModal.js
│       │   │   ├── MemoizedPatternPreview.js
│       │   ├── custom-events.js
│       │   ├── index.js
│       ├── plugins
│       │   └── consumer-site
│       │       └── index.js
│       ├── store
│       │   └── index.js
├── build
│   ├── blocks
│   ├── css
│   │   └── editor.css
│   ├── js
│   │   ├── admin.asset.php
│   │   ├── admin.js
│   │   ├── editor-rtl.css
│   │   ├── editor.asset.php
│   │   ├── editor.js
│   │   ├── main.asset.php
│   │   └── main.js
├── bin
│   └── phpcbf.sh
├── inc
│   ├── classes
│   │   ├── class-assets.php
│   │   ├── class-cpt-restriction.php
│   │   ├── class-hooks.php
│   │   ├── class-plugin.php
│   │   ├── class-rest.php
│   │   ├── class-settings.php
│   │   └── post-type
│   │       ├── class-base.php
│   │       ├── class-design-library.php
│   │       ├── class-meta.php
│   ├── helpers
│   └── traits
│       └── trait-singleton.php
├── vendor
│   └── ...
├── babel.config.js
├── composer.json
├── composer.lock
├── CONTRIBUTING.md
├── DEVELOPMENT.md
├── onedesign.php
├── package.json
├── phpcs.xml.dist
├── README.md
├── webpack.config.js
```

</details>

## Local setup

To set up locally, clone the repository into plugins directory of your WordPress installation:

### Prerequisites
- [Node.js](https://nodejs.org/) v20+
- npm or yarn
- PHP (recommended: 7.4+)
- Composer
- WordPress (recommended: 6.8+) (local install)

### Building OneDesign Packages

Install dependencies:
```bash
  # Navigate to the plugin directory
  composer install
  npm install
```

Start the development build process:
```bash
  npm start
```

Create a production-ready build:
```bash
  npm run build:prod
```

## Code Contributions (Pull Requests)

### Workflow
The `develop` branch is used for active development, while `main` contains the current stable release. Always create a new branch from `develop` when working on a new feature or bug fix.

Branches should be prefixed with the type of change (e.g. `feat`, `chore`, `tests`, `fix`, etc.) followed by a short description of the change. For example, a branch for a new feature called "Add new feature" could be named `feat/add-new-feature`.


### Code Quality / Code Standards
This project uses several tools to ensure code quality and standards are maintained:

#### ESLint
This project uses [ESLint](https://eslint.org), which is a tool for identifying and reporting on patterns found in ECMAScript/JavaScript code.

You can run ESLint using the following command:

```bash
  npm run lint
```

ESLint can automatically fix some issues. To fix issues automatically, run:

```bash
  npm run lint:fix
```

## Changesets

This project uses [Changesets](https://github.com/changesets/changesets) for versioning and generating changelogs across the packages in the repo.

To generate a Changeset (_copied and modified from [Changesets' docs](https://github.com/changesets/changesets/blob/01c037c0462540196b5d3d0c0241d8752b465b4b/docs/adding-a-changeset.md)_):

1. Run `npm run changeset` in the root of the monorepo.
2. Select the packages you want to include in the changeset.
    - Use `↑` and `↓` to navigate to packages.
    - Use `space` to select a package.
    - Hit `enter` when all desired packages are selected.
3. You will be prompted to select a bump type for each selected package. First you will flag all the packages that should receive a `major` bump, then `minor`. The remaining packages will receive a `patch` bump.

    - **Major**: Any form of breaking change.
    - **Minor**: New (non-breaking) features or changes.
    - **Patch**: Bug fixes.

4. Your final prompt will be to provide a message to go along with the changeset. This message will be written to the changeset when the next release is made.

   > ⚠️ **Important**
   >
   > Remember to follow [Conventional Commits formatting](https://www.conventionalcommits.org/en/v1.0.0/) and to use imperative language in your changeset message.
   >
   > For example, "feat: Add new feature" instead of "Added new feature".

   After this, a new changeset will be added which is a markdown file with YAML front matter.

    ```
    -| .changeset/
    -|-| UNIQUE_ID.md
    ```

   The message you typed can be found in the markdown file. If you want to expand on it, you can write as much markdown as you want, which will all be added to the changelog on publish. If you want to add more packages or change the bump types of any packages, that's also fine.

5. Once you are happy with the changeset, commit the file to your branch.


## Releasing
1. Ensure all changes are committed and tested.
2. Update changelogs and version numbers.
3. Merge to main branch.
4. Tag release and push to remote.
5. Publish packages if needed.

### Release Commands

Command to create a tag and push it:
```bash
git tag -a vx.x.x -m "Release vx.x.x"
git push --tags
```

Command to delete the tag (Locally) incase wanted to release same tag:
```bash
git tag --delete vx.x.x
```

Release will be auto generated and kept in draft once pushed a tag.
