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
- Back Office:
  - `/admin/user` (name: `app_admin_user_index`) - Manage all users
  - `/admin/user/new` (name: `app_admin_user_new`) - Create new user
  - `/admin/user/{id}` (name: `app_admin_user_show`) - View user details
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
  - `/auctions/{id}/withdraw` (name: `app_bet_session_withdraw`) - Withdraw auction [POST]
  - `/auctions/{id}/edit` (name: `app_bet_session_edit`) - Edit auction
  - `/auctions/{id}` (name: `app_bet_session_delete`) - Delete auction [POST]
  - `/bid/add` (name: `app_add_bid`) - Add bid to auction [POST]
- Back Office:
  - `/admin/auctions/All` (name: `app_admin_bet_session_index`) - Manage all auctions
  - `/admin/auctions/create` (name: `app_admin_bet_session_create`) - Create auction
  - `/admin/auctions/{id}` (name: `app_admin_bet_session_show`) - View auction
  - `/admin/auctions/{id}` (name: `app_admin_bet_session_delete`) - Delete auction [POST]

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
  - `/trade/dispute/{id}` (name: `app_trade_dispute_show`) - View dispute
  - `/trade/dispute/{id}/edit` (name: `app_trade_dispute_edit`) - Edit dispute
  - `/trade/dispute/{id}` (name: `app_trade_dispute_delete`) - Delete dispute [POST]
- Back Office:
  - `/admin/dispute` (name: `app_admin_trade_dispute_index`) - Manage disputes
  - `/admin/dispute/add` (name: `app_admin_trade_dispute_add`) - Add dispute
  - `/admin/dispute/{id}/show` (name: `app_admin_trade_dispute_show`) - View dispute
  - `/admin/dispute/{id}/edit` (name: `app_admin_trade_dispute_edit`) - Edit dispute
  - `/admin/dispute/{id}/delete` (name: `app_admin_trade_dispute_delete`) - Delete dispute [POST]

##### **7. Categories**
- Front Office:
  - `/category` (name: `app_category_index`) - Browse categories
  - `/category/new` (name: `app_category_new`) - Create category
  - `/category/{id}` (name: `app_category_show`) - View category
  - `/category/{id}/edit` (name: `app_category_edit`) - Edit category
  - `/category/{id}` (name: `app_category_delete`) - Delete category [POST]
  - `/category/{id}/info` (name: `app_category_info`) - Get category info [GET API]
- Back Office:
  - `/admin/category` (name: `app_admin_category_index`) - Manage categories
  - `/admin/category/new` (name: `app_admin_category_new`) - Create category
  - `/admin/category/{id}/edit` (name: `app_admin_category_edit`) - Edit category
  - `/admin/category/{id}` (name: `app_admin_category_delete`) - Delete category [POST]
  - `/admin/category/{id}/info` (name: `app_admin_category_info`) - Get category info [GET API]

##### **8. Blog System**
- Front Office:
  - `/blog` (name: `app_blog_index`) - View blog posts
  - `/blog/new` (name: `app_blog_new`) - Create new post
  - `/blog/{id}` (name: `app_blog_show`) - Read post
  - `/blog/{id}/edit` (name: `app_blog_edit`) - Edit post
  - `/blog/{id}/delete` (name: `app_blog_delete`) - Delete post [POST]
  - `/blog/{id}/comment` (name: `app_blog_add_comment`) - Add comment to post [POST]
  - `/blog/{id}/comment` (name: `app_blog_add_comment_to_blog`) - Add comment on post page [POST]
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

##### **10. Raffle Management**
- Front Office:
  - `/raffle` (name: `app_raffle_index`) - Browse all raffles
  - `/raffle/new` (name: `app_raffle_new`) - Create new raffle
  - `/raffle/{id}` (name: `app_raffle_show`) - View raffle details
  - `/raffle/{id}/join` (name: `app_raffle_join`) - Join a raffle
  - `/raffle/{id}/edit` (name: `app_raffle_edit`) - Edit raffle
  - `/raffle/{id}/participants` (name: `app_raffle_participants`) - View participants
  - `/raffle/{id}` (name: `app_raffle_delete`) - Delete raffle [POST]
- Back Office:
  - `/admin/raffle` (name: `app_back_raffle_index`) - Manage all raffles
  - `/admin/raffle/new` (name: `app_back_raffle_new`) - Create raffle
  - `/admin/raffle/{id}` (name: `app_back_raffle_show`) - View raffle
  - `/admin/raffle/{id}/edit` (name: `app_back_raffle_edit`) - Edit raffle
  - `/admin/raffle/{id}/delete` (name: `app_back_raffle_delete`) - Delete raffle [POST]
  - `/admin/raffle/{id}/participants` (name: `app_back_raffle_participants`) - View participants

##### **11. Participant Management**
- Front Office:
  - `/participant` (name: `app_participant_index`) - View all participants
  - `/participant/new` (name: `app_participant_new`) - Create new participant
  - `/participant/{id}` (name: `app_participant_show`) - View participant details
  - `/participant/{id}/edit` (name: `app_participant_edit`) - Edit participant
  - `/participant/{id}` (name: `app_participant_delete`) - Delete participant [POST]
- Back Office:
  - `/participant/admin` (name: `app_participant_admin`) - Manage participants
  - `/participant/admin/{id}` (name: `app_participant_show_admin`) - View participant details

#### **Development Best Practices**  
- Validate **user forms only at the Entity level**.  
- Use `{% form_start(form, {'attr': {'novalidate': 'novalidate'}} ) %}` in Twig for form handling.

