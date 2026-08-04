<?php

namespace CartRelay\App\Components\Forms;

use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Describes one form field and its processing rules.
 */
class Field {

	private string $id;
	private string $label = '';
	private string $description = '';
	private mixed $default = null;
	private string $placeholder = '';
	private array $options = [];
	private array $attributes = [];
	private array $classes = [];
	private bool $disabled = false;
	private bool $readonly = false;
	private bool $required = false;
	private ?array $requiredCondition = null;
	private array $rules = [];
	private mixed $sanitizer = null;
	private array $messages = [];
	private string $help = '';
	private ?array $visibility = null;
	private array $dependencies = [];

	public function __construct(
		private readonly string $name,
		private readonly FieldType $type
	) {
		if ( ! preg_match( '/^[A-Za-z][A-Za-z0-9_-]*$/', $name ) ) {
			throw new InvalidArgumentException( "Invalid field name {$name}." );
		}

		$this->id = 'cr-' . str_replace( '_', '-', $name );
	}

	public function id( string $id ): static {
		$this->id = $id;
		return $this;
	}

	public function label( string $label ): static {
		$this->label = $label;
		return $this;
	}

	public function description( string $description ): static {
		$this->description = $description;
		return $this;
	}

	public function default( mixed $default ): static {
		$this->default = $default;
		return $this;
	}

	public function placeholder( string $placeholder ): static {
		$this->placeholder = $placeholder;
		return $this;
	}

	public function options( array $options ): static {
		$this->options = $options;
		return $this;
	}

	public function attributes( array $attributes ): static {
		$this->attributes = array_merge( $this->attributes, $attributes );
		return $this;
	}

	public function classes( string ...$classes ): static {
		$this->classes = array_values( array_unique( [ ...$this->classes, ...$classes ] ) );
		return $this;
	}

	public function disabled( bool $disabled = true ): static {
		$this->disabled = $disabled;
		return $this;
	}

	public function readonly( bool $readonly = true ): static {
		$this->readonly = $readonly;
		return $this;
	}

	public function required( ?string $message = null ): static {
		$this->required = true;
		return $this->rule( 'required', message: $message );
	}

	public function requiredWhen( string $field, mixed $value = true, ?string $message = null ): static {
		$this->required          = true;
		$this->requiredCondition = compact( 'field', 'value' );

		return $this->rule(
			'required',
			message: $message,
			when: static fn( array $values ): bool => ( $values[ $field ] ?? null ) === $value
		);
	}

	public function rule(
		string $name,
		array $parameters = [],
		?string $message = null,
		?callable $when = null
	): static {
		$this->rules[] = compact( 'name', 'parameters', 'message', 'when' );
		return $this;
	}

	public function sanitize( string|callable $sanitizer ): static {
		$this->sanitizer = $sanitizer;
		return $this;
	}

	public function message( string $rule, string $message ): static {
		$this->messages[ $rule ] = $message;
		return $this;
	}

	public function help( string $help ): static {
		$this->help = $help;
		return $this;
	}

	public function visibleWhen( string $field, mixed $value = true ): static {
		$this->visibility = compact( 'field', 'value' );
		return $this->dependsOn( $field );
	}

	public function dependsOn( string ...$fields ): static {
		$this->dependencies = array_values( array_unique( [ ...$this->dependencies, ...$fields ] ) );
		return $this;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getType(): FieldType {
		return $this->type;
	}

	public function getId(): string {
		return $this->id;
	}

	public function getLabel(): string {
		return $this->label;
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function getDefault(): mixed {
		return $this->default;
	}

	public function getPlaceholder(): string {
		return $this->placeholder;
	}

	public function getOptions(): array {
		return $this->options;
	}

	public function getAttributes(): array {
		return $this->attributes;
	}

	public function getClasses(): array {
		return $this->classes;
	}

	public function isDisabled(): bool {
		return $this->disabled;
	}

	public function isReadonly(): bool {
		return $this->readonly;
	}

	public function isRequired(): bool {
		return $this->required;
	}

	public function getRequiredCondition(): ?array {
		return $this->requiredCondition;
	}

	public function getRules(): array {
		return $this->rules;
	}

	public function getSanitizer(): mixed {
		return $this->sanitizer;
	}

	public function getMessage( string $rule ): ?string {
		return $this->messages[ $rule ] ?? null;
	}

	public function getHelp(): string {
		return $this->help;
	}

	public function getVisibility(): ?array {
		return $this->visibility;
	}

	public function getDependencies(): array {
		return $this->dependencies;
	}

}
