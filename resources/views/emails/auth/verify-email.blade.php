<!DOCTYPE html>
<html lang="id" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Verifikasi Email Anda - Lumina Library</title>

    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body, table, td, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background-color: #f3f4f5 !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        table {
            border-spacing: 0;
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        a {
            text-decoration: none;
        }

        .primary-btn:hover {
            background-color: #1a2b48 !important;
        }

        .text-link:hover {
            color: #1a2b48 !important;
        }

        @media screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }

            .main-card {
                border-radius: 0 !important;
                border-left: none !important;
                border-right: none !important;
            }

            .content-padding {
                padding: 32px 20px !important;
            }

            .btn-wrapper {
                width: 100% !important;
                display: block !important;
            }

            .primary-btn {
                display: block !important;
                width: auto !important;
                box-sizing: border-box !important;
            }

            .title-text {
                font-size: 22px !important;
                line-height: 30px !important;
            }
        }
    </style>
</head>

<body style="margin: 0; padding: 0; background-color: #f3f4f5; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f5; padding: 40px 0;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table class="email-container main-card" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 16px; border: 1px solid #e1e3e4; box-shadow: 0 4px 24px rgba(0,0,0,0.04); margin: 0 auto; width: 600px; max-width: 600px;">
                    <tr>
                        <td align="center" class="content-padding" style="padding: 40px 40px 24px 40px;">
                            <img
                                src="{{ $logoUrl }}"
                                alt="Lumina Library"
                                width="180"
                                style="display: block; border: 0; max-width: 100%; height: auto;"
                            />
                        </td>
                    </tr>

                    <tr>
                        <td class="content-padding" style="padding: 0 40px 48px 40px; text-align: center;">
                            <h2 class="title-text" style="margin: 0 0 16px 0; font-size: 24px; font-weight: 700; line-height: 32px; color: #031632; letter-spacing: -0.01em;">
                                Verifikasi Email Anda
                            </h2>

                            <p style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; line-height: 24px; color: #031632;">
                                Halo, {{ $user->fullname }}
                            </p>

                            <p style="margin: 0 0 32px 0; font-size: 16px; font-weight: 400; line-height: 26px; color: #44474d;">
                                Terima kasih telah bergabung. Satu langkah lagi untuk menyelesaikan pendaftaran Anda di Lumina Library. Silakan klik tombol di bawah ini untuk mengaktifkan akun Anda.
                            </p>

                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <table border="0" cellpadding="0" cellspacing="0" class="btn-wrapper">
                                            <tr>
                                                <td align="center" style="border-radius: 9999px; background-color: #031632;">
                                                    <a href="{{ $verificationUrl }}" class="primary-btn" target="_blank" style="display: inline-block; padding: 16px 36px; font-family: 'Inter', Arial, sans-serif; font-size: 16px; font-weight: 600; line-height: 24px; color: #ffffff; text-decoration: none; border-radius: 9999px; transition: background-color 0.2s ease;">
                                                        Verifikasi Email
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 40px; margin-bottom: 24px;">
                                <tr>
                                    <td style="border-top: 1px solid #edeeef;"></td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 12px 0; font-size: 14px; font-weight: 400; line-height: 22px; color: #75777e; text-align: left;">
                                Tombol tidak berfungsi? Salin dan tempel tautan ini ke browser Anda:
                            </p>

                            <div style="background-color: #f8f9fa; padding: 16px; border-radius: 12px; border: 1px solid #e1e3e4; word-break: break-all; text-align: left;">
                                <a href="{{ $verificationUrl }}" class="text-link" target="_blank" style="font-size: 14px; font-weight: 500; line-height: 22px; color: #2a9ad9; text-decoration: none;">
                                    {{ $verificationUrl }}
                                </a>
                            </div>
                        </td>
                    </tr>
                </table>

                <table class="email-container" border="0" cellpadding="0" cellspacing="0" width="600" style="margin: 0 auto; width: 600px; max-width: 600px;">
                    <tr>
                        <td align="center" style="padding: 24px 20px; text-align: center;">
                            <p style="margin: 0 0 8px 0; font-size: 12px; font-weight: 400; line-height: 18px; color: #75777e;">
                                Pesan ini dikirim secara otomatis. Jika Anda tidak merasa melakukan pendaftaran, Anda dapat mengabaikan atau menghapus email ini.
                            </p>
                            <p style="margin: 0; font-size: 12px; font-weight: 500; line-height: 18px; color: #44474d;">
                                &copy; {{ $year }} Lumina Library. Hak cipta dilindungi undang-undang.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>