# Project Documentation: Grade Remarks Generator

**Course**: ITEP 308 – System Integration and Architecture I
**Project**: Web Application with Machine Learning Integration

---

## 1. Project Overview

**Title**: Grade Remarks Generator
**Problem Definition**:
Educators often spend excessive time manually analyzing student grades and attendance to determine qualitative performance remarks (e.g., "Excellent", "Needs Improvement"). This manual process is prone to inconsistency and fatigue.

**Purpose**:
To develop a PHP-based Web Application that integrates a Machine Learning library (PHP-ML) to automatically predict and generate consistent performance remarks based on quantitative student data (Grades, Attendance, Assignments, Exams).

---

## 2. Design Thinking Application

### A. Hills (The Vision)

- **Who**: Teachers and Academic Instructors.
- **What**: Enable them to instantly generate accurate performance remarks based on multiple data points.
- **Wow**: Reduce grading administrative time by automating the qualitative analysis with a single click.

### B. Sponsor User (Persona)

- **Name**: Ms. Rivera
- **Role**: Senior High School Teacher
- **Pain Points**: Overwhelmed by calculating final remarks for 200+ students; wants a way to quickly flag students who need improvement based on a holistic view of their data.

### C. Playback (Feedback & Improvements)

- **Feedback 1**: "The interface looks too basic and hard to read on my tablet."
  - **Improvement**: Implemented a **Premium Design System** (`style.css`) with a responsive card-based layout, modern typography (Inter font), and mobile compatibility.
- **Feedback 2**: "I sometimes enter the wrong data and want to clear it from the history."
  - **Improvement**: Added a **"Remove" button** to the Recent Predictions table, allowing users to manage their data effectively.
- **Feedback 3**: "I need to see a history of what I just processed."
  - **Improvement**: Integrated a **MySQL Database** to store and display the last 15 predictions in a clean, organized table.

---

## 3. System Architecture

### A. Architecture Diagram (Conceptual)

```mermaid
graph LR
    User[User / Teacher] -- Input Data --> UI[Web Interface (HTML/CSS)]
    UI -- POST Request --> App[PHP Application]
    App -- Load Model --> ML[ML Engine (PHP-ML)]
- **Deployed Site**: [Insert Your Hosting Link Here (e.g., Vercel/InfinityFree)]
- **Video Presentation**: [Insert Video Link]
- **Presentation Slides**: [Insert Canva/PPT Link]

References
PHP-ML Library: https://github.com/php-ai/php-ml
Composer: https://getcomposer.org/
Design Icons/Fonts: Google Fonts (Inter)
```
