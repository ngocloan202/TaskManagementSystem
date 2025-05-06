# TaskManagementSystem
A simple PHP + MySQL web app for Kanban‑style task/project management, using Tailwind CSS for styling.

## Prerequisites
- PHP 8.x  
- MySQL 5.7+  
- Node.js 14+ & npm  

## 1. Install Nodejs

```bash
npm i
```

We’ll keep all Tailwind source files under `frontend/` and output into `public/css/`.

```bash
npm install --save-dev postcss autoprefixer
```

## 3. Prepare your Tailwind source

Open (or create) the file:

```
frontend/css/input.css
```

Make sure it contains at least:

```css
@import 'tailwindcss';
```

## 4. Install the Tailwind CLI

```bash
npm install --save-dev tailwindcss @tailwindcss/cli
```

## 6. Build & Watch

```bash
# Build one time (minified)
npm run build

# During development, rebuild on changes
npm run watch
```

This will generate/update:
```
public/css/tailwind.css
```

## 7. Include Tailwind in your layout

In your main layout (e.g. `app/Views/layouts/header.php`), add:

```html
<link rel="stylesheet" href="/css/tailwind.css">
```

…then start using Tailwind utility classes in your Views.

---

Happy coding! 🚀  