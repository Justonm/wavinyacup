# Wavinya Cup - Client Presentation Guide

## 1. Introduction

- **Purpose:** Welcome the client and introduce the Wavinya Cup Team Registration System.
- **Vision:** A centralized, secure, and efficient platform to manage the entire tournament registration process, from individual players to county-level administration.
- **Problem Solved:** Replaces manual, paper-based processes with a streamlined digital solution, ensuring data accuracy, transparency, and accessibility.

---

## 2. System Goals & Key Achievements

- **Efficiency:** Automate registration and approval workflows to save time and reduce administrative overhead.
- **Data Integrity:** A single source of truth for all player, team, and coach information.
- **Security:** Robust security measures to protect sensitive user data.
- **Scalability:** Built to handle the registration of all teams across Machakos County's 8 sub-counties and 33 wards.
- **Accessibility:** Easy-to-use dashboards for all user roles, from players to county administrators.

---

## 3. Core Features Showcase

- **Hierarchical Administrative Structure:** The system mirrors the real-world administrative structure of Machakos County (County -> Sub-County -> Ward).
- **Role-Based Access Control (RBAC):** Each user has specific permissions based on their role, ensuring they only see and manage what they are authorized to.
- **Automated Approval Workflows:** A multi-level approval process for teams and players, ensuring proper vetting at each administrative level.
- **Secure Coach Self-Registration:** Coaches can independently register themselves and their teams, which then go through an admin approval process.
- **Secure Admin Login:** Implemented Gmail OAuth 2.0 for admin access, providing an extra layer of security through Google's authentication.
- **Comprehensive Dashboards:** Tailored dashboards for each user role, providing relevant data and management tools.
- **Automated Email Notifications:** The system sends automated emails for key events, such as registration confirmation and account approval.

---

## 4. User Roles & Live Demonstration Flow

This section can be used as a script for a live demonstration.

### Step 1: The Public View & Coach Registration

- **Action:** Navigate to the public-facing homepage (`public_index.php`).
- **Talking Points:**
    - "This is the main landing page where coaches and players can find information about the tournament."
    - "Let's walk through the process of a new coach registering their team."
- **Action:** Click on the 'Coach Self-Registration' link (`/coaches/self_register.php`).
- **Talking Points:**
    - "Coaches can fill out this simple form to register themselves and create a new team."
    - "This information is then sent to the system administrators for approval."

### Step 2: The Admin's Perspective - Approving a Coach

- **Action:** Log in as a County Administrator using the secure Gmail OAuth login (`/auth/admin_login.php`).
- **Talking Points:**
    - "For administrators, we've implemented a highly secure login system using their official Gmail accounts, which supports two-factor authentication."
- **Action:** Navigate to the 'Pending Coaches' section in the admin dashboard (`/admin/pending_coaches.php`).
- **Talking Points:**
    - "Here, the administrator can see all the coaches awaiting approval."
    - "They can review the details and, with a single click, approve or reject a registration."
    - "Upon approval, the system automatically sends a welcome email to the coach with their login credentials."

### Step 3: The Coach's Experience - Managing a Team

- **Action:** Log in with the newly approved coach's credentials (`/auth/coach_login.php`).
- **Talking Points:**
    - "Once approved, the coach can log in to their dedicated dashboard."
- **Action:** Navigate to the 'Manage Team' or 'Add Players' section (`/coach/manage_team.php`).
- **Talking Points:**
    - "From their dashboard, the coach can manage their team details and, most importantly, add players."
    - "The system enforces a rule of a maximum of 22 players per team, ensuring compliance with tournament regulations."

### Step 4: The Captain and Player View

- **Action:** Log in as a Team Captain.
- **Talking Points:**
    - "Captains have a view-only access to their team and player list, allowing them to coordinate with the team effectively."
- **Action:** Log in as a Player.
- **Talking Points:**
    - "Players can log in to view their own profile and registration status."

### Step 5: Higher-Level Administrative Dashboards

- **Action:** Log back in as an administrator and showcase the different dashboards (Ward, Sub-County, County).
- **Talking Points:**
    - "The system provides a top-down view for administrators at every level."
    - "A Ward administrator can see all the teams in their ward, a Sub-County admin sees all wards in their sub-county, and the County admin has a complete overview."
    - "This hierarchical view is crucial for reporting and overall tournament management."

---

## 5. Technical & Security Overview

- **Technology Stack:** Built on a reliable and widely-used stack: PHP for the backend, MySQL for the database, and a standard HTML/CSS/JavaScript front-end.
- **Security First:**
    - **Data Protection:** We use prepared statements to prevent SQL injection and sanitize all user inputs.
    - **Secure Authentication:** Passwords are securely hashed, and we've implemented Google OAuth for the highest level of admin security.
    - **Role-Based Access:** A strict permission system ensures data is never exposed to unauthorized users.

---

## 6. Future Enhancements

- **Mobile Application:** A dedicated mobile app for players and coaches for on-the-go updates.
- **Advanced Analytics:** Deeper insights into player statistics and team performance.
- **Live Match Updates:** Real-time scoring and updates during the tournament.

---

## 7. Q&A

- Open the floor for any questions from the client.
