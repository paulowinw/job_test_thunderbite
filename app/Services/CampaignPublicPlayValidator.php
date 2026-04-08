<?php

namespace App\Services;

use App\Exceptions\CampaignValidationException;
use App\Models\Campaign;

class CampaignPublicPlayValidator
{
    public function validate(Campaign $campaign): void
    {
        $tz = $campaign->timezone ?? config('app.timezone');
        $now = now()->timezone($tz);

        if ($campaign->starts_at !== null && $now->lt($campaign->starts_at)) {
            throw new CampaignValidationException(__('The campaign has not started yet.'));
        }

        if ($campaign->ends_at !== null && $now->gt($campaign->ends_at)) {
            throw new CampaignValidationException(__('This campaign has ended.'));
        }
    }
}
