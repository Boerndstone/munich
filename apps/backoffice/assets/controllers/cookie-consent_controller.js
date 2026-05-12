import { Controller } from "stimulus";
import * as CookieConsent from "vanilla-cookieconsent";

export default class extends Controller {
  connect() {
    // Initialize CookieConsent plugin
    CookieConsent.run({
      categories: {
        necessary: {
          enabled: true,
          readOnly: true,
        },
        analytics: {},
      },
      language: {
        default: "de",
        translations: {
          de: {
            consentModal: {
              title: "Cookies auf unserer Website.",
              description:
                "Mit der Nutzung unserer Website erklären Sie sich einverstanden, dass wir Cookies verwenden.",
              acceptAllBtn: "Akzeptieren",
              // acceptNecessaryBtn: "Reject all",
              showPreferencesBtn: "Weitere Informationen",
            },
            preferencesModal: {
              title: "Cookie Einstellungen",
              acceptAllBtn: "Alle akzeptieren",
              acceptNecessaryBtn: "Alle ablehnen",
              savePreferencesBtn: "Einstellungen speichern",
              closeIconLabel: "Close modal",
              sections: [
                {
                  title: "Informationen zu unseren Cookie Einstellungen",
                  // description:
                  //   "Wenn Sie auf unsere Website zugreifen, d.h., wenn Sie sich nicht registrieren oder anderweitig Informationen übermitteln, werden automatisch Informationen allgemeiner Natur erfasst. Diese Informationen (Server-Logfiles) beinhalten etwa die Art des Webbrowsers, das verwendete Betriebssystem, den Domainnamen Ihres Internet-Service-Providers, Ihre IP-Adresse und ähnliches. Hierbei handelt es sich ausschließlich um Informationen, welche keine Rückschlüsse auf Ihre Person zulassen.",
                },
                {
                  title: "Tracking Cookies",
                  description:
                    "Wenn Sie auf unsere Website zugreifen, d.h., wenn Sie sich nicht registrieren oder anderweitig Informationen übermitteln, werden automatisch Informationen allgemeiner Natur erfasst. Diese Informationen (Server-Logfiles) beinhalten etwa die Art des Webbrowsers, das verwendete Betriebssystem, den Domainnamen Ihres Internet-Service-Providers, Ihre IP-Adresse und ähnliches. Hierbei handelt es sich ausschließlich um Informationen, welche keine Rückschlüsse auf Ihre Person zulassen.",
                  linkedCategory: "necessary",
                },
                // {
                //   title: "Performance and Analytics",
                //   description:
                //     "These cookies collect information about how you use our website. All of the data is anonymized and cannot be used to identify you.",
                //   linkedCategory: "analytics",
                // },
                // {
                //   title: "More information",
                //   description:
                //     'For any queries in relation to my policy on cookies and your choices, please <a href="#contact-page">contact us</a>',
                // },
              ],
            },
          },
        },
      },
    });
  }
}
