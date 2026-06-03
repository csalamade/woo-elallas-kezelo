# WooCommerce Elállás Kezelő

Egy professzionális WooCommerce bővítmény, amely automatizálja a vásárlói elállásokat és rendelés lemondásokat, teljes mértékben megfelelve a fogyasztóvédelmi jogszabályoknak (14 napos elállási jog).

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

## Telepítés és Beállítás
1. Másold a `woo-elallas-kezelo` mappát a `/wp-content/plugins/` könyvtárba, vagy tömörítsd be ZIP-be és töltsd fel a WordPress adminban.
2. Kapcsold be a bővítményt a *Bővítmények* menüpontban.
3. Vendég vásárlók számára hozd létre (ha még nincs) a szabványos WooCommerce "Rendeléskövetés" oldalt, ahova beilleszted a `[woocommerce_order_tracking]` shortcode-ot. Az elállási/lemondási űrlap automatikusan beépül a lekérdezett rendelés adatlapjába.
4. Lépj be a *WooCommerce -> Elállás Kezelő* menüpontba, és állítsd be:
   - Mely státuszok számítanak "feladás előttinek".
   - Mi legyen a "teljesített" státusz, ahonnan a 14 nap indul.
   - Hány nap a szállítási idő.
   - A sikeres beküldés után megjelenő egyedi üzenetet.
5. Lépj be a *WooCommerce -> Beállítások -> E-mailek* fülre, és szabd testre az "Elállás / Lemondás" e-maileket.

## Technikai Információk
A bővítmény a legjobb WordPress és WooCommerce programozási gyakorlatokat (Best Practices) követi:
- **Sebességoptimalizált:** Nem hoz létre felesleges egyedi adatbázis táblákat, nem okoz "autoload bloat"-ot a wp_options táblában.
- **HPOS Kompatibilis:** Támogatja a WooCommerce High-Performance Order Storage (HPOS) funkcióját.
- **Megbízható Validáció:** A kliens oldali (JavaScript) ellenőrzés mellett teljeskörű szerver oldali (PHP) validációt is használ, amely session-öktől függetlenül, cache-elt oldalakon is stabilan jeleníti meg a hibaüzeneteket.
