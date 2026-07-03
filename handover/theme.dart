// Lentera — design tokens for Flutter (mobile app).
// Source of truth: Lentera - Android.dc.html (open in browser, DevTools > Computed).
// Fonts via google_fonts: Quicksand (display), Nunito (body).
//
//   dependencies:
//     google_fonts: ^6.2.1
//
// Usage: MaterialApp(theme: LenteraTheme.light, darkTheme: LenteraTheme.dark)
//        Color accent = LenteraAccent.mint; // user-tweakable (mint | clay | lavender)

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class LenteraLight {
  static const bg        = Color(0xFFFAF8F3);
  static const card      = Color(0xFFFFFFFF);
  static const text      = Color(0xFF3B3A44);
  static const text2     = Color(0xFF6B6976);
  static const text3     = Color(0xFF7A7886);
  static const dim       = Color(0xFF9C99A6);
  static const dim2      = Color(0xFFB0ADB8);
  static const line      = Color(0xFFF4F1EB);
  static const line2     = Color(0xFFE4E1D9);
  static const mintSoft  = Color(0xFFE6F4EC);
  static const peachSoft = Color(0xFFFBEAE1);
  static const lavSoft   = Color(0xFFECE8F8);
  static const lavText   = Color(0xFF5E5780);
}

class LenteraDark {
  static const bg        = Color(0xFF17161C);
  static const card      = Color(0xFF232229);
  static const text      = Color(0xFFECEAF1);
  static const text2     = Color(0xFFB4B1BE);
  static const text3     = Color(0xFFA8A5B3);
  static const dim       = Color(0xFF8C8995);
  static const dim2      = Color(0xFF76737E);
  static const line      = Color(0xFF322F39);
  static const line2     = Color(0xFF34313B);
  static const chipOff   = Color(0xFF2E2D36);
  static const mintSoft  = Color(0xFF1E3327);
  static const peachSoft = Color(0xFF382620);
  static const lavSoft   = Color(0xFF29243E);
  static const lavText   = Color(0xFFC7BEEA);
}

class LenteraAccent {
  static const mint     = Color(0xFF5CB88A); // default
  static const mintDeep = Color(0xFF3F9D72);
  static const clay     = Color(0xFFCD7A54);
  static const clayDeep = Color(0xFFE08A66);
  static const lavender = Color(0xFF7E72B8);
  static const lavDeep  = Color(0xFF8B7FC4);
  // accentGlow: accent.withOpacity(0.30)
  static Color glow(Color a) => a.withOpacity(0.30);
}

class LenteraRadius {
  static const pill = 999.0, card = 24.0, cardLg = 26.0, button = 18.0, chip = 16.0, icon = 14.0, sm = 11.0;
}

class LenteraSpace {
  static const xs = 4.0, sm = 8.0, md = 12.0, lg = 16.0, xl = 20.0, screen = 24.0;
}

class LenteraShadow {
  static const card = [BoxShadow(color: Color(0x0D463C5A), blurRadius: 20, offset: Offset(0, 6))]; // rgba(70,60,90,.05)
  static const raised = [BoxShadow(color: Color(0x0F463C5A), blurRadius: 24, offset: Offset(0, 8))];
}

// Type scale — Quicksand for display/headings/titles, Nunito for body.
class LenteraType {
  static TextStyle display(Color c) => GoogleFonts.quicksand(fontSize: 30, fontWeight: FontWeight.w700, letterSpacing: -0.3, color: c);
  static TextStyle h1(Color c)      => GoogleFonts.quicksand(fontSize: 27, fontWeight: FontWeight.w700, color: c);
  static TextStyle h2(Color c)      => GoogleFonts.quicksand(fontSize: 22, fontWeight: FontWeight.w700, color: c);
  static TextStyle title(Color c)   => GoogleFonts.quicksand(fontSize: 16, fontWeight: FontWeight.w700, color: c);
  static TextStyle body(Color c)    => GoogleFonts.nunito(fontSize: 15, fontWeight: FontWeight.w500, height: 1.55, color: c);
  static TextStyle small(Color c)   => GoogleFonts.nunito(fontSize: 12.5, fontWeight: FontWeight.w600, color: c);
  static TextStyle micro(Color c)   => GoogleFonts.nunito(fontSize: 11, fontWeight: FontWeight.w700, color: c);
}

class LenteraTheme {
  static ThemeData get light => _build(Brightness.light);
  static ThemeData get dark  => _build(Brightness.dark);

  static ThemeData _build(Brightness b) {
    final isDark = b == Brightness.dark;
    final bg   = isDark ? LenteraDark.bg : LenteraLight.bg;
    final card = isDark ? LenteraDark.card : LenteraLight.card;
    final text = isDark ? LenteraDark.text : LenteraLight.text;
    return ThemeData(
      brightness: b,
      scaffoldBackgroundColor: bg,
      cardColor: card,
      primaryColor: LenteraAccent.mint,
      colorScheme: ColorScheme.fromSeed(seedColor: LenteraAccent.mint, brightness: b)
          .copyWith(surface: card, background: bg),
      textTheme: GoogleFonts.nunitoTextTheme().apply(bodyColor: text, displayColor: text),
      useMaterial3: true,
    );
  }
}
