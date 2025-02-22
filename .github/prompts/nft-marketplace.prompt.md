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
- Back Office:
  - `/admin/login` (name: `admin_login`) - Admin login

##### **2. User Management (Admin)**
- `/admin/user/` (name: `app_admin_user_index`) - List all users
- `/admin/user/new` (name: `app_admin_user_new`) - Create new user
- `/admin/user/{id}` (name: `app_admin_user_show`) - Show user details
- `/admin/user/{id}/edit` (name: `app_admin_user_edit`) - Edit user
- `/admin/user/{id}/roles` (name: `app_admin_user_roles`) - Manage user roles
- `/admin/user/{id}` (name: `app_admin_user_delete`) - Delete user [POST]

##### **3. Artwork Management**
- Front Office:
  - `/artwork` (name: `app_artwork_index`) - Browse artworks
  - `/artwork/{id}` (name: `app_artwork_show`) - View artwork details
- Back Office:
  - `/admin/artwork/` (name: `app_admin_artwork_index`) - List all artworks
  - `/admin/artwork/new` (name: `app_admin_artwork_new`) - Add new artwork
  - `/admin/artwork/{id}/edit` (name: `app_admin_artwork_edit`) - Edit artwork
  - `/admin/artwork/{id}` (name: `app_admin_artwork_delete`) - Delete artwork [POST]

##### **4. Auctions System**
- Front Office:
  - `/auctions/` (name: `app_bet_session_active`) - View active auctions
  - `/auctions/new` (name: `app_bet_session_new`) - Create new auction
  - `/auctions/List/{userId}` (name: `app_bet_session_mylist`) - User's auctions
  - `/auctions/ItemDetails/{id}` (name: `app_item_details`) - Auction details
- Back Office:
  - `/admin/auctions/All` (name: `app_bet_session_index`) - Manage all auctions
  - `/admin/auctions/create` (name: `app_bet_session_create`) - Create auction
  - `/admin/auctions/{id}` (name: `app_bet_session_delete_admin`) - Delete auction [POST]

##### **5. Trading System**
- Front Office:
  - `/trade/offer` (name: `app_trade_offer_index`) - List trade offers
  - `/trade/offer/add` (name: `app_trade_offer_add`) - Create trade offer
  - `/trade/offer/{id}` (name: `app_trade_show`) - View offer details
  - `/trade/offer/{id}/edit` (name: `app_trade_offer_edit`) - Edit offer
- Back Office:
  - `/admin/trade` (name: `app_back_trade`) - Manage trades
  - `/admin/trade/add` (name: `app_back_trade_offer_add`) - Add trade
  - `/admin/trade/{id}/edit` (name: `app_back_trade_offer_edit`) - Edit trade
  - `/admin/trade/{id}/delete` (name: `app_back_trade_delete`) - Delete trade [POST]

##### **6. Dispute Management**
- Front Office:
  - `/trade/dispute` (name: `app_trade_dispute_index`) - List disputes
  - `/trade/dispute/new` (name: `app_trade_dispute_new`) - Create dispute
- Back Office:
  - `/admin/dispute` (name: `app_trade_dispute_back`) - Manage disputes
  - `/admin/dispute/add` (name: `app_back_trade_dispute_add`) - Add dispute
  - `/admin/dispute/{id}/edit` (name: `app_back_trade_dispute_edit`) - Edit dispute
  - `/admin/dispute/{id}/delete` (name: `app_back_trade_dispute_delete`) - Delete dispute [POST]

##### **7. Categories**
- Front Office:
  - `/category` (name: `app_category_index`) - Browse categories
  - `/category/{id}` (name: `app_category_show`) - View category
- Back Office:
  - `/admin/category` (name: `app_admin_category_index`) - Manage categories
  - `/admin/category/new` (name: `app_admin_category_new`) - Create category
  - `/admin/category/{id}/edit` (name: `app_admin_category_edit`) - Edit category
  - `/admin/category/{id}` (name: `app_admin_category_delete`) - Delete category [POST]

##### **8. Blog System**
- Front Office:
  - `/blog` (name: `app_blog_index`) - View blog posts
  - `/blog/{id}` (name: `app_blog_show`) - Read post
- Back Office:
  - `/admin/blog` (name: `app_admin_blog_index`) - Manage posts
  - `/admin/blog/new` (name: `app_admin_blog_new`) - Create post
  - `/admin/blog/{id}/edit` (name: `app_admin_blog_edit`) - Edit post
  - `/admin/blog/{id}/delete` (name: `app_admin_blog_delete`) - Delete post [POST]

##### **9. Comments**
- Front Office:
  - `/comment` (name: `app_comment_index`) - View comments
  - `/comment/new/{blogId}` (name: `app_comment_new`) - Add comment
- Back Office:
  - `/admin/comments` (name: `app_comment_back_index`) - Manage comments
  - `/admin/comments/{id}/edit` (name: `app_comment_back_edit`) - Edit comment
  - `/admin/comments/{id}/delete` (name: `app_comment_back_delete`) - Delete comment [POST]

#### **Development Best Practices**  
- Validate **user forms only at the Entity level**.  
- Use `{% form_start(form, {'attr': {'novalidate': 'novalidate'}} ) %}` in Twig for form handling.

