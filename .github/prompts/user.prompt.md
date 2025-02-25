# User Module Documentation
This documentation covers the User & Authentication related features of the NFT Marketplace.

### **NFT Marketplace – Symfony**  

#### **Project Overview**  
You are working on an **NFT Marketplace** using **Symfony (Web)**. The project includes modules for managing artworks, auctions, trades, and user interactions.

#### **General Guidelines**  
- Use **Symfony 6.4** for the web app with **Twig templates**.  
- **Front Office Route:** `/` (name: `app_home_page`) - Main user-facing interface
- **Back Office Route:** `/admin` (name: `app_home_page_back`) - Admin interface
- Keep **Twig templates modular**, separating **Front Office** and **Back Office** assets.  

#### **Modules & Features**  

##### **1. Authentication**
- Front Office:
  - `/login` (name: `app_login`) - User login
  - `/logout` (name: `app_logout`) - Logout
  - `/register` (name: `app_register`) - User registration
  - `/access-denied` (name: `app_access_denied`) - Access denied page
- Back Office:
  - `/admin/login` (name: `back_login`) - Admin login
  - `/admin/logout` (name: `app_logout`) - Admin logout
  - `/admin/access-denied` (name: `app_access_denied`) - Admin access denied

##### **2. User & Profile Management**
- Front Office:
  - `/user` (name: `app_user_index`) - View all users [Admin only]
  - `/user/{id}` (name: `app_user_show`) - View user profile
  - `/user/{id}/edit` (name: `app_user_edit`) - Edit user profile
  - `/user/{id}` (name: `app_user_delete`) - Delete user [POST]
  - `/user/topup/request` (name: `app_user_topup_request`) - Request balance top-up [GET/POST]
- Back Office:
  - `/admin/user` (name: `app_admin_user_index`) - Manage all users
  - `/admin/user/new` (name: `app_admin_user_new`) - Create new user
  - `/admin/user/{id}` (name: `app_admin_user_show`) - View user details
  - `/admin/user/{id}/edit` (name: `app_admin_user_edit`) - Edit user
  - `/admin/user/{id}/roles` (name: `app_admin_user_roles`) - Manage user roles
  - `/admin/user/{id}` (name: `app_admin_user_delete`) - Delete user [POST]
  - `/admin/topup-requests` (name: `app_admin_topup_index`) - Manage top-up requests
  - `/admin/topup-requests/{id}` (name: `app_admin_topup_approve`) - Approve top-up request [POST]
  - `/admin/topup-requests/{id}` (name: `app_admin_topup_reject`) - Reject top-up request [POST]

#### **Development Best Practices**  
- Validate **user forms only at the Entity level**.  
- Use `{% form_start(form, {'attr': {'novalidate': 'novalidate'}} ) %}` in Twig for form handling.