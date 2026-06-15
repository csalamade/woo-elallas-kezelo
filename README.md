# WooCommerce Elállás Kezelő

Egy professzionális WooCommerce bővítmény, amely automatizálja a vásárlói elállásokat és rendelés lemondásokat, teljes mértékben megfelelve a fogyasztóvédelmi jogszabályoknak (14 napos elállási jog).

## Telepítés és Letöltés

A bővítmény hivatalosan is elérhető a WordPress.org tárolójában! A legfrissebb, stabil verziót az alábbi gombra kattintva tudod letölteni, vagy akár közvetlenül a weboldalad admin felületéről is telepítheted.

[![Letöltés a WordPress.org-ról](https://img.shields.io/badge/WordPress.org-Letöltés-21759b?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org/plugins/elallas-kezelo/)

**Telepítési lehetőségek:**

**A) Automatikus telepítés (Ajánlott)**
1. Lépj be a WordPress admin felületedre.
2. Menj a **Bővítmények -> Új bővítmény hozzáadása** menüpontba.
3. A jobb felső keresőbe írd be: **Elállás Kezelő** (vagy `elallas-kezelo`).
4. Kattints a **Telepítés most**, majd a **Bekapcsolás** gombra.

**B) Manuális telepítés (.zip fájlból)**
1. Kattints a fenti kék gombra a hivatalos WordPress.org oldal megnyitásához.
2. Töltsd le a bővítményt a **Download** gombbal.
3. A WordPress adminban: **Bővítmények -> Új bővítmény hozzáadása -> Bővítmény feltöltése**.
4. Tallózd be a letöltött .zip fájlt, majd telepítsd és kapcsold be.

## Funkciók
- **Natív WooCommerce integráció:** Nincs szükség külön shortcode-ra! A bővítmény teljesen beépül a WooCommerce gyári **Rendeléskövetés** (`[woocommerce_order_tracking]`) felületébe és a regisztrált vásárlók **Fiókom -> Rendelések** oldalára. A gomb és az űrlap automatikusan megjelenik a rendelés részletei alatt.
- **Intelligens határidő számítás:** Különbséget tesz a feladás előtti lemondás és a feladás utáni elállás között. Feladás után figyelembe veszi a szállítási átfutási időt is, így pontosan tudja, mikor vette át a csomagot a vásárló.
- **Részleges visszaküldés:** A vásárló termékenként, jelölőnégyzetek segítségével döntheti el, hogy a rendelésből mit szeretne visszaküldeni.
- **Automatikus WooCommerce E-mailek:** 
  - *Vásárlói visszaigazoló:* Részletes lista a visszaküldött termékekről és a további teendőkről (tartós adathordozóként funkcionál).
  - *Admin értesítő:* Külön e-mail a webshop adminisztrátorának a részletekkel.
  - Mindkét e-mail a hivatalos *WooCommerce -> Beállítások -> E-mailek* menüpont alatt testreszabható!
- **Admin felületi könnyítések:**
  - Külön menüpont a beállításoknak (*WooCommerce -> Elállás Kezelő*).
  - Automatikus rendelési jegyzet (Order Note) készül minden elálláskor.
  - A részlegesen lemondott termékek a rendelés szerkesztő felületén feltűnő **[LEMONDVA / ELÁLLÁS]** piros címkét kapnak, hogy a raktár ne csomagolja be őket véletlenül.
  - Automatikus rendelés státusz frissítések (pl. teljes lemondás esetén "Visszamondva", részlegesnél "Felfüggesztve").



## Technikai Információk
A bővítmény a legjobb WordPress és WooCommerce programozási gyakorlatokat (Best Practices) követi:
- **Sebességoptimalizált:** Nem hoz létre felesleges egyedi adatbázis táblákat, nem okoz "autoload bloat"-ot a wp_options táblában.
- **HPOS Kompatibilis:** Támogatja a WooCommerce High-Performance Order Storage (HPOS) funkcióját.
- **Megbízható Validáció:** A kliens oldali (JavaScript) ellenőrzés mellett teljeskörű szerver oldali (PHP) validációt is használ, amely session-öktől függetlenül, cache-elt oldalakon is stabilan jeleníti meg a hibaüzeneteket.
