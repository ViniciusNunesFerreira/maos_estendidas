<div class="form-group">
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-3">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="space-y-3 {{ $inline ? 'sm:flex sm:space-y-0 sm:space-x-6' : '' }}">
        @foreach($options as $optionValue => $optionLabel)
            <x-forms.radio
                :name="$name"
                :value="(string)$optionValue"
                :label="$optionLabel"
                :checked="$old() == $optionValue"
                :required="$required && $loop->first"
            />
        @endforeach
    </div>

    @if($help && !$hasError())
        <p class="mt-2 text-sm text-gray-500">{{ $help }}</p>
    @endif

    @if($hasError())
        @foreach($errors() as $error)
            <p class="mt-2 text-sm text-red-600">{{ $error }}</p>
        @endforeach
    @endif
</div>