<?php

namespace App\Enums;

enum OrderSystemStatus: string
{
    case IN_APP                 = 'in_app';
    case SEND_TO_PROVIDER       = 'send_to_provider';
    case GET_FROM_PROVIDER      = 'get_from_provider';
    case SEND_TO_SDG_INBOUND    = 'send_to_sdg_inbound';
    case GET_FROM_SDG_ARV       = 'get_from_sdg_arv';
    case SEND_TO_SDG_OUTBOUND   = 'send_to_sdg_outbound';
    case GET_FROM_SDG_SHP       = 'get_from_sdg_shp';
    case GET_FROM_SDG_WBL       = 'get_from_sdg_wbl';
    case GET_FROM_ATS_IN_RADIUS = 'get_from_ats_in_radius';
    case GET_FROM_ATS_ON_POINT  = 'get_from_ats_on_point';
    case GET_FROM_ATS_DONE      = 'get_from_ats_done';
    case GET_FROM_ATS_CANCEL    = 'get_from_ats_cancel';
}