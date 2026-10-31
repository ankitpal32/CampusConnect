# CampusConnect Documentation Index

This directory contains the core product, technical, and operational documentation for the **CampusConnect** college event platform.

---

## Documentation Structure

```text
documents/
├── product/
│   ├── prd.md             # Product requirements, vision, user personas, & features
│   └── phases.md          # Implementation roadmap, milestones, & delivery phases
├── technical/
│   ├── architecture.md    # System architecture, database schema, & auth flows
│   └── design.md          # UI/UX design tokens, visual hierarchy, & design system
├── guidelines/
│   ├── rules.md           # Engineering guidelines, security rules, & coding standards
│   └── memory.md          # Project context, decisions log, & evolution notes
└── README.md              # This documentation directory index
```

---

## Document References

### 1. Product & Planning (`product/`)
- [**Product Requirements Document (`prd.md`)**](product/prd.md): Defines the core problem statement, audience (students and event administrators), functional specifications, and user journeys.
- [**Development Phases (`phases.md`)**](product/phases.md): Tracks feature rollout, milestones, and deliverables from foundational setup to production polish.

### 2. Architecture & Design (`technical/`)
- [**Architecture Specification (`architecture.md`)**](technical/architecture.md): Documents the Core PHP 8.x + MySQL + PDO architecture, database entities (`users`, `events`, `registrations`, `attendance`), session models, and transactional locking.
- [**Design System (`design.md`)**](technical/design.md): Describes the editorial visual identity, Deep Violet (`#6842e8`) color palette, typography scale (Outfit & Inter), component specifications, and accessibility requirements.

### 3. Standards & Context (`guidelines/`)
- [**Development Rules (`rules.md`)**](guidelines/rules.md): Coding conventions, prepared statements, CSRF protection, input validation rules, and error handling protocols.
- [**Project Memory (`memory.md`)**](guidelines/memory.md): Ongoing project context, key design and architectural decisions, resolved technical debts, and pair programming log.
