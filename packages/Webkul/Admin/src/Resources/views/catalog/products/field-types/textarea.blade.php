<textarea v-validate="'{{$validations}}'" class="control" id="{{ $attribute->code }}" name="{{ $attribute->code }}" data-vv-as="&quot;{{ $attribute->admin_name }}&quot;" {{ $disabled ? 'disabled' : '' }}>{{ old($attribute->code) ?: $product[$attribute->code]}}</textarea>