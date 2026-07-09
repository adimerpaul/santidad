import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// Paleta tomada del diseño de referencia (ejemploApp.html).
class AppColors {
  AppColors._();

  static const ink = Color(0xFF22304A);
  static const inkSoft = Color(0xFF31456B);
  static const muted = Color(0xFF8B94A6);
  static const muted2 = Color(0xFF5B6779);

  static const primary = Color(0xFF2F6FC1);
  static const primaryDark = Color(0xFF1E5AA8);
  static const primaryDeep = Color(0xFF174785);
  static const primarySoft = Color(0xFFE3EDF9);
  static const primaryPale = Color(0xFFCFE2F7);
  static const primaryLight = Color(0xFF9CC4F0);

  static const cream = Color(0xFFFAFCFE);
  static const cream2 = Color(0xFFEFF4FA);
  static const line = Color(0xFFE2E9F2);
  static const line2 = Color(0xFFD9E3EF);

  static const wa = Color(0xFF25D366);
  static const waDark = Color(0xFF1DA851);

  static const sky = Color(0xFFE9F2FB);
  static const skyInk = Color(0xFF4A78A8);

  static const okBg = Color(0xFFE5F3EA);
  static const okFg = Color(0xFF2E7D4F);
  static const warnBg = Color(0xFFFDF3D7);
  static const warnFg = Color(0xFF8A6D1A);
  static const badBg = Color(0xFFFBE7E4);
  static const badFg = Color(0xFFC0392B);

  static const infoBg = Color(0xFFE3F2FD);
  static const infoFg = Color(0xFF2A5B8F);
}

ThemeData buildAppTheme() {
  final base = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(
      seedColor: AppColors.primary,
      primary: AppColors.primary,
      surface: AppColors.cream,
    ),
    scaffoldBackgroundColor: AppColors.cream2,
  );

  return base.copyWith(
    textTheme: GoogleFonts.plusJakartaSansTextTheme(base.textTheme).apply(
      bodyColor: AppColors.ink,
      displayColor: AppColors.ink,
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AppColors.cream,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: AppColors.line2, width: 1.5),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: AppColors.line2, width: 1.5),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
      ),
      hintStyle: const TextStyle(color: AppColors.muted, fontSize: 13.5),
    ),
  );
}
