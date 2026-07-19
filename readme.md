# Drupal Market Watchlist - Development Notes

## Introduction

This is my first time using Drupal. My previous experience has mainly been with plain PHP, Laravel, and CakePHP.

This document explains my approach, setup instructions, assumptions made during development, trade-offs, possible improvements, and approximate time spent.

---

# 1. Setup Instructions

All setup instructions, from cloning the repository until enabling the module, are documented separately in:

```
drupal.md
```

The document includes:

- Cloning the repository
- Starting Docker environment
- Installing dependencies
- Setting up Drupal
- Enabling the module
- Required Drush commands

---

# 2. Assumptions Made

## AI-Assisted Development

Most of the implementation was generated with AI assistance.

However, every generated file was carefully inspected to ensure:

- The implementation matches the requirements
- The code flow is understood
- The generated code behaves as expected

---

## Understanding of Drupal Code Flow

Every generated file was reviewed and understood, including how the code moves through:

- Module metadata setup
- Migration
- Routing
- Controller
- Form
- Permission

The goal was not only to generate working code, but also to understand how each Drupal component connects together.

---

# 3. Trade-offs and Improvements With More Time

The current implementation focuses on completing the required functionality.

With additional time, the following improvements could be considered.

---

## 3.1 Dedicated Theme Module

As far as I can tell, creating another module with type:

```
theme
```

could improve the overall presentation and rendering of the page.

This would allow better separation between:

- Application logic
- Page rendering
- User interface improvements

---

## 3.2 Seeder and Unit Tests

Implement:

- Seeder
- Unit tests

Benefits:

- Easier testing
- More consistent development data
- Better confidence when making changes

---

## 3.3 Proper Logging Mechanism

Implement a proper logging mechanism.

This would help with:

- Debugging
- Tracking errors
- Monitoring important operations

---

## 3.4 Validator Service

Introduce a dedicated:

```
ValidatorService
```

to make the controller more readable and focused on a single responsibility.

The idea is to separate:

- Validation rules
- Controller logic

The same approach could also be applied to other areas such as:

- Database handling
- Events
- Output/resource handling

This would improve separation of concerns and maintainability.

---

# 4. Approximate Time Spent

## Part A - Around 2 Hours 30 Minutes

Activities:

- Setup Docker
- Setup Drupal
- Understanding how Drupal works
- Setup Git

---

## Part B - Around 25 Minutes

Activities:

- Solving issues
- Testing

---

## Part C - Around 35 Minutes

Activities:

- Understanding issues/code
- Solving common issues:
  - N+1 query
  - SQL Injection

- Enhancing overall code quality

---

# Final Notes

The implementation was completed while learning Drupal's structure and workflow.

The main focus was:

- Understanding Drupal module architecture
- Ensuring the implementation matches the requirements
- Reviewing generated code carefully
- Improving overall code quality where possible
