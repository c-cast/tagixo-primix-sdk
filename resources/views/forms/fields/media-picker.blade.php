{{-- The component comes from the core's media-picker.js, registered on the
     LiVue app by the media gallery capability. --}}
<tgx-media-picker-field
    id="{{ $id }}"
    v-model="{{ $statePath }}"
    @if($multiple) :multiple="true" @endif
    @if($maxFiles !== null) :max-files="{{ $maxFiles }}" @endif
    @if(!empty($acceptedTypes)) :accepted-types='@json($acceptedTypes, JSON_UNESCAPED_SLASHES)' @endif
    @if($disabled) disabled @endif
    @if($placeholder) placeholder="{{ $placeholder }}" @endif
></tgx-media-picker-field>
