```
symfony server:start 
symfony serve
```

Recommended Folder Structure
Inside public/, create two separate folders:

```
public/
├── front_office/
│   ├── css/
│   ├── js/
│   ├── images/
│   ├── fonts/
├── back_office/
│   ├── css/
│   ├── js/
│   ├── images/
│   ├── fonts/
```
This way, you keep frontend (user-facing) and backend (admin panel) assets isolated.

Using Assets in Twig
In your Twig templates, update asset links accordingly.

Front Office Example (templates/front/home.html.twig)

```twig
<link rel="stylesheet" href="{{ asset('front_office/css/style.css') }}">
<script src="{{ asset('front_office/js/script.js') }}"></script>
<img src="{{ asset('front_office/images/logo.png') }}" alt="Logo">
```

Back Office Example (templates/back/dashboard.html.twig)

```twig
<link rel="stylesheet" href="{{ asset('back_office/css/admin.css') }}">
<script src="{{ asset('back_office/js/admin.js') }}"></script>
<img src="{{ asset('back_office/images/admin-logo.png') }}" alt="Admin Logo">
```