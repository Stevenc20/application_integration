import math
from django import template

register = template.Library()

@register.filter(name='roundup')
def roundup(value):
    try:
        return math.ceil(float(value))
    except (ValueError, TypeError):
        # Jika data tidak valid, kembalikan 0
        return 0