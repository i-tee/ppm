import { useI18n } from "vue-i18n";

export function usePayoutStatus() {
    const { t } = useI18n();

    // Тексты статусов (fallback, если в API нет status_text)
    const getStatusText = (status) => {
        const statuses = {
            0: t("payoutRequest.status.created"),
            10: t("payoutRequest.status.approved"),
            14: t("payoutRequest.status.paid_whait_ticket"),
            16: t("payoutRequest.status.ticket_uploaded"),
            20: t("payoutRequest.status.paid"),
            50: t("payoutRequest.status.cancelled"),
            99: t("payoutRequest.status.deleted"),
        };
        return statuses[status] || t("payoutRequest.status.unknown");
    };

    // Цвета статусов (чисто, без i18n)
    const PAYOUT_STATUS_COLORS = {
        0: "warning",
        10: "info",
        14: "danger", // красный — чтобы юзер сразу заметил, что нужно загрузить чек
        16: "primary",
        20: "success",
        50: "danger",
        99: "danger",
    };

    const getStatusColor = (status) => PAYOUT_STATUS_COLORS[status] || "gray";

    return {
        getStatusText,
        getStatusColor,
    };
}
