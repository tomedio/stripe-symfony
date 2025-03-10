# Contributing to Documentation

This guide explains how to work with the documentation for the Stripe Bundle.

## Local Development

To work on the documentation locally, you'll need to have Ruby and Bundler installed.

### Prerequisites

- Ruby 2.7 or higher
- Bundler

### Setup

1. Clone the repository:

```bash
git clone https://github.com/tomedio/stripe-symfony.git
cd stripe-symfony
```

2. Install dependencies:

```bash
cd docs
bundle install
```

3. Start the local server:

```bash
bundle exec jekyll serve
```

4. Open your browser and navigate to `http://localhost:4000/stripe-symfony/`

### Making Changes

The documentation is written in Markdown and organized in the `docs/` directory:

- `docs/_docs/`: Contains the main documentation content, organized by section
- `docs/index.md`: The landing page
- `docs/_config.yml`: Jekyll configuration

When adding new pages, make sure to include the proper front matter at the top of each file:

```yaml
---
layout: default
title: Your Page Title
parent: Parent Section
nav_order: 1
---
```

## Automatic Deployment

The documentation is automatically deployed to GitHub Pages when changes are pushed to the `main` branch. The deployment is handled by a GitHub Actions workflow defined in `.github/workflows/deploy-docs.yml`.

The workflow:

1. Checks out the code
2. Sets up Ruby
3. Installs dependencies
4. Builds the Jekyll site
5. Deploys the built site to the `gh-pages` branch

You can check the status of the deployment in the "Actions" tab of the GitHub repository.

## Best Practices

- Use clear, concise language
- Include code examples where appropriate
- Use headings to organize content
- Include a table of contents for longer pages
- Use relative links to reference other pages in the documentation
- Test your changes locally before pushing
