# IntraSphere - Plateforme E-learning Moderne

## Overview
IntraSphere is a modern, flexible Learning Management System (LMS) designed to provide a comprehensive and adaptive learning experience for multiple educational establishments, coupled with robust administrative capabilities. Its core vision is to offer a scalable, multi-tenant e-learning platform that centralizes management while isolating data per establishment. The project aims to provide a complete enterprise-grade LMS solution, including advanced content creation (WYSIWYG editor), assessment tools, group functionalities, and real-time communication features, with a focus on modern UI/UX and efficient performance.

## User Preferences
*Aucune préférence spécifique documentée pour le moment*

## System Architecture
IntraSphere is built with a modern, modular architecture organized by business domains to enhance maintainability and scalability.

**UI/UX Decisions:**
- **Design System:** Utilizes Shadcn/ui and Tailwind CSS for a modern, responsive, and accessible interface.
- **Theming:** Supports both dark and light themes with an integrated switcher.
- **Responsiveness:** Mobile-first design approach ensuring optimal viewing across various devices.
- **Navigation:** Intuitive and user-friendly navigation structure.

**Technical Implementations:**
- **Frontend:** React TypeScript.
- **Backend:** Node.js with Express.js, featuring a multi-tenant architecture.
- **Database:** PostgreSQL, managed with Drizzle ORM for type-safe queries and schema migrations.
- **State Management:** Tanstack Query for efficient data fetching, caching, and synchronization.
- **Access Control:** Role-Based Access Control (RBAC) system with granular permissions: Super Admin, Admin, Manager, Formateur, and Apprenant.
- **Project Structure:** Organized into `frontend/`, `backend/`, `shared/` (for common types and schemas), `config/`, and `deployment/` directories. Business logic is separated into features (e.g., auth, admin, content, training) on the frontend and services/routes on the backend.
- **API Design:** RESTful API with modular routes organized by domain (e.g., `/api/auth`, `/api/establishments`, `/api/courses`, `/api/users`).
- **Real-time Communication:** Integrated WebSocket for real-time features and notifications.

**Feature Specifications:**
- **Multi-establishment Management:** Complete multi-tenant architecture with centralized establishment management and data isolation.
- **Content Management:** Advanced WYSIWYG editor for course creation, supporting reusable components.
- **LMS Core:** Comprehensive learning functionalities including course management, assessments, study groups, and progression tracking.

## External Dependencies
- **Frontend Libraries:** React, Tanstack Query, Shadcn/ui, Tailwind CSS.
- **Backend Frameworks:** Node.js, Express.js.
- **Database:** PostgreSQL.
- **ORM:** Drizzle ORM.
- **TypeScript:** Used across both frontend and backend for type safety.
- **Build Tool:** Vite (for frontend development and build).