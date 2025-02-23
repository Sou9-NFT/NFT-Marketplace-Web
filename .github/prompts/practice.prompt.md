### **NFT Marketplace – Symfony**  

#### **Project Overview**  
You are working on an **NFT Marketplace** using **Symfony (Web)**. The project includes modules for managing artworks, auctions, trades, and user interactions.

#### **General Guidelines**  
- Use **Symfony 6.4** for the web app with **Twig templates**.  
- **Front Office Route:** `/` (name: `app_home_page`) - Main user-facing interface
- **Back Office Route:** `/admin` (name: `app_home_page_back`) - Admin interface
- Keep **Twig templates modular**, separating **Front Office** and **Back Office** assets.  

#### **Development Best Practices**  
- Validate **user forms only at the Entity level**.  
- Use `{% form_start(form, {'attr': {'novalidate': 'novalidate'}} ) %}` in Twig for form handling.