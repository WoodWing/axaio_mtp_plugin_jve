/**
 * PropertyInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class PropertyInfo  implements java.io.Serializable {
    private java.lang.String name;

    private java.lang.String displayName;

    private java.lang.String category;

    private com.woodwing.enterprise.interfaces.services.wfl.PropertyType type;

    private java.lang.String defaultValue;

    private java.lang.String[] valueList;

    private java.lang.String minValue;

    private java.lang.String maxValue;

    private java.lang.Integer maxLength;

    private com.woodwing.enterprise.interfaces.services.wfl.PropertyValue[] propertyValues;

    private java.lang.String parentValue;

    private com.woodwing.enterprise.interfaces.services.wfl.Property[] dependentProperties;

    private java.lang.String minResolution;

    private java.lang.String maxResolution;

    private com.woodwing.enterprise.interfaces.services.wfl.DialogWidget[] widgets;

    private java.lang.String termEntity;

    private java.lang.String suggestionEntity;

    private java.lang.String autocompleteProvider;

    private java.lang.String suggestionProvider;

    private java.lang.String publishSystemId;

    private com.woodwing.enterprise.interfaces.services.wfl.PropertyNotification[] notifications;

    private java.lang.Boolean mixedValues;

    public PropertyInfo() {
    }

    public PropertyInfo(
           java.lang.String name,
           java.lang.String displayName,
           java.lang.String category,
           com.woodwing.enterprise.interfaces.services.wfl.PropertyType type,
           java.lang.String defaultValue,
           java.lang.String[] valueList,
           java.lang.String minValue,
           java.lang.String maxValue,
           java.lang.Integer maxLength,
           com.woodwing.enterprise.interfaces.services.wfl.PropertyValue[] propertyValues,
           java.lang.String parentValue,
           com.woodwing.enterprise.interfaces.services.wfl.Property[] dependentProperties,
           java.lang.String minResolution,
           java.lang.String maxResolution,
           com.woodwing.enterprise.interfaces.services.wfl.DialogWidget[] widgets,
           java.lang.String termEntity,
           java.lang.String suggestionEntity,
           java.lang.String autocompleteProvider,
           java.lang.String suggestionProvider,
           java.lang.String publishSystemId,
           com.woodwing.enterprise.interfaces.services.wfl.PropertyNotification[] notifications,
           java.lang.Boolean mixedValues) {
           this.name = name;
           this.displayName = displayName;
           this.category = category;
           this.type = type;
           this.defaultValue = defaultValue;
           this.valueList = valueList;
           this.minValue = minValue;
           this.maxValue = maxValue;
           this.maxLength = maxLength;
           this.propertyValues = propertyValues;
           this.parentValue = parentValue;
           this.dependentProperties = dependentProperties;
           this.minResolution = minResolution;
           this.maxResolution = maxResolution;
           this.widgets = widgets;
           this.termEntity = termEntity;
           this.suggestionEntity = suggestionEntity;
           this.autocompleteProvider = autocompleteProvider;
           this.suggestionProvider = suggestionProvider;
           this.publishSystemId = publishSystemId;
           this.notifications = notifications;
           this.mixedValues = mixedValues;
    }


    /**
     * Gets the name value for this PropertyInfo.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this PropertyInfo.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the displayName value for this PropertyInfo.
     * 
     * @return displayName
     */
    public java.lang.String getDisplayName() {
        return displayName;
    }


    /**
     * Sets the displayName value for this PropertyInfo.
     * 
     * @param displayName
     */
    public void setDisplayName(java.lang.String displayName) {
        this.displayName = displayName;
    }


    /**
     * Gets the category value for this PropertyInfo.
     * 
     * @return category
     */
    public java.lang.String getCategory() {
        return category;
    }


    /**
     * Sets the category value for this PropertyInfo.
     * 
     * @param category
     */
    public void setCategory(java.lang.String category) {
        this.category = category;
    }


    /**
     * Gets the type value for this PropertyInfo.
     * 
     * @return type
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PropertyType getType() {
        return type;
    }


    /**
     * Sets the type value for this PropertyInfo.
     * 
     * @param type
     */
    public void setType(com.woodwing.enterprise.interfaces.services.wfl.PropertyType type) {
        this.type = type;
    }


    /**
     * Gets the defaultValue value for this PropertyInfo.
     * 
     * @return defaultValue
     */
    public java.lang.String getDefaultValue() {
        return defaultValue;
    }


    /**
     * Sets the defaultValue value for this PropertyInfo.
     * 
     * @param defaultValue
     */
    public void setDefaultValue(java.lang.String defaultValue) {
        this.defaultValue = defaultValue;
    }


    /**
     * Gets the valueList value for this PropertyInfo.
     * 
     * @return valueList
     */
    public java.lang.String[] getValueList() {
        return valueList;
    }


    /**
     * Sets the valueList value for this PropertyInfo.
     * 
     * @param valueList
     */
    public void setValueList(java.lang.String[] valueList) {
        this.valueList = valueList;
    }


    /**
     * Gets the minValue value for this PropertyInfo.
     * 
     * @return minValue
     */
    public java.lang.String getMinValue() {
        return minValue;
    }


    /**
     * Sets the minValue value for this PropertyInfo.
     * 
     * @param minValue
     */
    public void setMinValue(java.lang.String minValue) {
        this.minValue = minValue;
    }


    /**
     * Gets the maxValue value for this PropertyInfo.
     * 
     * @return maxValue
     */
    public java.lang.String getMaxValue() {
        return maxValue;
    }


    /**
     * Sets the maxValue value for this PropertyInfo.
     * 
     * @param maxValue
     */
    public void setMaxValue(java.lang.String maxValue) {
        this.maxValue = maxValue;
    }


    /**
     * Gets the maxLength value for this PropertyInfo.
     * 
     * @return maxLength
     */
    public java.lang.Integer getMaxLength() {
        return maxLength;
    }


    /**
     * Sets the maxLength value for this PropertyInfo.
     * 
     * @param maxLength
     */
    public void setMaxLength(java.lang.Integer maxLength) {
        this.maxLength = maxLength;
    }


    /**
     * Gets the propertyValues value for this PropertyInfo.
     * 
     * @return propertyValues
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PropertyValue[] getPropertyValues() {
        return propertyValues;
    }


    /**
     * Sets the propertyValues value for this PropertyInfo.
     * 
     * @param propertyValues
     */
    public void setPropertyValues(com.woodwing.enterprise.interfaces.services.wfl.PropertyValue[] propertyValues) {
        this.propertyValues = propertyValues;
    }


    /**
     * Gets the parentValue value for this PropertyInfo.
     * 
     * @return parentValue
     */
    public java.lang.String getParentValue() {
        return parentValue;
    }


    /**
     * Sets the parentValue value for this PropertyInfo.
     * 
     * @param parentValue
     */
    public void setParentValue(java.lang.String parentValue) {
        this.parentValue = parentValue;
    }


    /**
     * Gets the dependentProperties value for this PropertyInfo.
     * 
     * @return dependentProperties
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Property[] getDependentProperties() {
        return dependentProperties;
    }


    /**
     * Sets the dependentProperties value for this PropertyInfo.
     * 
     * @param dependentProperties
     */
    public void setDependentProperties(com.woodwing.enterprise.interfaces.services.wfl.Property[] dependentProperties) {
        this.dependentProperties = dependentProperties;
    }


    /**
     * Gets the minResolution value for this PropertyInfo.
     * 
     * @return minResolution
     */
    public java.lang.String getMinResolution() {
        return minResolution;
    }


    /**
     * Sets the minResolution value for this PropertyInfo.
     * 
     * @param minResolution
     */
    public void setMinResolution(java.lang.String minResolution) {
        this.minResolution = minResolution;
    }


    /**
     * Gets the maxResolution value for this PropertyInfo.
     * 
     * @return maxResolution
     */
    public java.lang.String getMaxResolution() {
        return maxResolution;
    }


    /**
     * Sets the maxResolution value for this PropertyInfo.
     * 
     * @param maxResolution
     */
    public void setMaxResolution(java.lang.String maxResolution) {
        this.maxResolution = maxResolution;
    }


    /**
     * Gets the widgets value for this PropertyInfo.
     * 
     * @return widgets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.DialogWidget[] getWidgets() {
        return widgets;
    }


    /**
     * Sets the widgets value for this PropertyInfo.
     * 
     * @param widgets
     */
    public void setWidgets(com.woodwing.enterprise.interfaces.services.wfl.DialogWidget[] widgets) {
        this.widgets = widgets;
    }


    /**
     * Gets the termEntity value for this PropertyInfo.
     * 
     * @return termEntity
     */
    public java.lang.String getTermEntity() {
        return termEntity;
    }


    /**
     * Sets the termEntity value for this PropertyInfo.
     * 
     * @param termEntity
     */
    public void setTermEntity(java.lang.String termEntity) {
        this.termEntity = termEntity;
    }


    /**
     * Gets the suggestionEntity value for this PropertyInfo.
     * 
     * @return suggestionEntity
     */
    public java.lang.String getSuggestionEntity() {
        return suggestionEntity;
    }


    /**
     * Sets the suggestionEntity value for this PropertyInfo.
     * 
     * @param suggestionEntity
     */
    public void setSuggestionEntity(java.lang.String suggestionEntity) {
        this.suggestionEntity = suggestionEntity;
    }


    /**
     * Gets the autocompleteProvider value for this PropertyInfo.
     * 
     * @return autocompleteProvider
     */
    public java.lang.String getAutocompleteProvider() {
        return autocompleteProvider;
    }


    /**
     * Sets the autocompleteProvider value for this PropertyInfo.
     * 
     * @param autocompleteProvider
     */
    public void setAutocompleteProvider(java.lang.String autocompleteProvider) {
        this.autocompleteProvider = autocompleteProvider;
    }


    /**
     * Gets the suggestionProvider value for this PropertyInfo.
     * 
     * @return suggestionProvider
     */
    public java.lang.String getSuggestionProvider() {
        return suggestionProvider;
    }


    /**
     * Sets the suggestionProvider value for this PropertyInfo.
     * 
     * @param suggestionProvider
     */
    public void setSuggestionProvider(java.lang.String suggestionProvider) {
        this.suggestionProvider = suggestionProvider;
    }


    /**
     * Gets the publishSystemId value for this PropertyInfo.
     * 
     * @return publishSystemId
     */
    public java.lang.String getPublishSystemId() {
        return publishSystemId;
    }


    /**
     * Sets the publishSystemId value for this PropertyInfo.
     * 
     * @param publishSystemId
     */
    public void setPublishSystemId(java.lang.String publishSystemId) {
        this.publishSystemId = publishSystemId;
    }


    /**
     * Gets the notifications value for this PropertyInfo.
     * 
     * @return notifications
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PropertyNotification[] getNotifications() {
        return notifications;
    }


    /**
     * Sets the notifications value for this PropertyInfo.
     * 
     * @param notifications
     */
    public void setNotifications(com.woodwing.enterprise.interfaces.services.wfl.PropertyNotification[] notifications) {
        this.notifications = notifications;
    }


    /**
     * Gets the mixedValues value for this PropertyInfo.
     * 
     * @return mixedValues
     */
    public java.lang.Boolean getMixedValues() {
        return mixedValues;
    }


    /**
     * Sets the mixedValues value for this PropertyInfo.
     * 
     * @param mixedValues
     */
    public void setMixedValues(java.lang.Boolean mixedValues) {
        this.mixedValues = mixedValues;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PropertyInfo)) return false;
        PropertyInfo other = (PropertyInfo) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.displayName==null && other.getDisplayName()==null) || 
             (this.displayName!=null &&
              this.displayName.equals(other.getDisplayName()))) &&
            ((this.category==null && other.getCategory()==null) || 
             (this.category!=null &&
              this.category.equals(other.getCategory()))) &&
            ((this.type==null && other.getType()==null) || 
             (this.type!=null &&
              this.type.equals(other.getType()))) &&
            ((this.defaultValue==null && other.getDefaultValue()==null) || 
             (this.defaultValue!=null &&
              this.defaultValue.equals(other.getDefaultValue()))) &&
            ((this.valueList==null && other.getValueList()==null) || 
             (this.valueList!=null &&
              java.util.Arrays.equals(this.valueList, other.getValueList()))) &&
            ((this.minValue==null && other.getMinValue()==null) || 
             (this.minValue!=null &&
              this.minValue.equals(other.getMinValue()))) &&
            ((this.maxValue==null && other.getMaxValue()==null) || 
             (this.maxValue!=null &&
              this.maxValue.equals(other.getMaxValue()))) &&
            ((this.maxLength==null && other.getMaxLength()==null) || 
             (this.maxLength!=null &&
              this.maxLength.equals(other.getMaxLength()))) &&
            ((this.propertyValues==null && other.getPropertyValues()==null) || 
             (this.propertyValues!=null &&
              java.util.Arrays.equals(this.propertyValues, other.getPropertyValues()))) &&
            ((this.parentValue==null && other.getParentValue()==null) || 
             (this.parentValue!=null &&
              this.parentValue.equals(other.getParentValue()))) &&
            ((this.dependentProperties==null && other.getDependentProperties()==null) || 
             (this.dependentProperties!=null &&
              java.util.Arrays.equals(this.dependentProperties, other.getDependentProperties()))) &&
            ((this.minResolution==null && other.getMinResolution()==null) || 
             (this.minResolution!=null &&
              this.minResolution.equals(other.getMinResolution()))) &&
            ((this.maxResolution==null && other.getMaxResolution()==null) || 
             (this.maxResolution!=null &&
              this.maxResolution.equals(other.getMaxResolution()))) &&
            ((this.widgets==null && other.getWidgets()==null) || 
             (this.widgets!=null &&
              java.util.Arrays.equals(this.widgets, other.getWidgets()))) &&
            ((this.termEntity==null && other.getTermEntity()==null) || 
             (this.termEntity!=null &&
              this.termEntity.equals(other.getTermEntity()))) &&
            ((this.suggestionEntity==null && other.getSuggestionEntity()==null) || 
             (this.suggestionEntity!=null &&
              this.suggestionEntity.equals(other.getSuggestionEntity()))) &&
            ((this.autocompleteProvider==null && other.getAutocompleteProvider()==null) || 
             (this.autocompleteProvider!=null &&
              this.autocompleteProvider.equals(other.getAutocompleteProvider()))) &&
            ((this.suggestionProvider==null && other.getSuggestionProvider()==null) || 
             (this.suggestionProvider!=null &&
              this.suggestionProvider.equals(other.getSuggestionProvider()))) &&
            ((this.publishSystemId==null && other.getPublishSystemId()==null) || 
             (this.publishSystemId!=null &&
              this.publishSystemId.equals(other.getPublishSystemId()))) &&
            ((this.notifications==null && other.getNotifications()==null) || 
             (this.notifications!=null &&
              java.util.Arrays.equals(this.notifications, other.getNotifications()))) &&
            ((this.mixedValues==null && other.getMixedValues()==null) || 
             (this.mixedValues!=null &&
              this.mixedValues.equals(other.getMixedValues())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getDisplayName() != null) {
            _hashCode += getDisplayName().hashCode();
        }
        if (getCategory() != null) {
            _hashCode += getCategory().hashCode();
        }
        if (getType() != null) {
            _hashCode += getType().hashCode();
        }
        if (getDefaultValue() != null) {
            _hashCode += getDefaultValue().hashCode();
        }
        if (getValueList() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getValueList());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getValueList(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMinValue() != null) {
            _hashCode += getMinValue().hashCode();
        }
        if (getMaxValue() != null) {
            _hashCode += getMaxValue().hashCode();
        }
        if (getMaxLength() != null) {
            _hashCode += getMaxLength().hashCode();
        }
        if (getPropertyValues() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPropertyValues());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPropertyValues(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getParentValue() != null) {
            _hashCode += getParentValue().hashCode();
        }
        if (getDependentProperties() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getDependentProperties());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getDependentProperties(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMinResolution() != null) {
            _hashCode += getMinResolution().hashCode();
        }
        if (getMaxResolution() != null) {
            _hashCode += getMaxResolution().hashCode();
        }
        if (getWidgets() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getWidgets());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getWidgets(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getTermEntity() != null) {
            _hashCode += getTermEntity().hashCode();
        }
        if (getSuggestionEntity() != null) {
            _hashCode += getSuggestionEntity().hashCode();
        }
        if (getAutocompleteProvider() != null) {
            _hashCode += getAutocompleteProvider().hashCode();
        }
        if (getSuggestionProvider() != null) {
            _hashCode += getSuggestionProvider().hashCode();
        }
        if (getPublishSystemId() != null) {
            _hashCode += getPublishSystemId().hashCode();
        }
        if (getNotifications() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getNotifications());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getNotifications(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getMixedValues() != null) {
            _hashCode += getMixedValues().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(PropertyInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PropertyInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("displayName");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DisplayName"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("category");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Category"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("type");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Type"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PropertyType"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("defaultValue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DefaultValue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("valueList");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ValueList"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("minValue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MinValue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("maxValue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MaxValue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("maxLength");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MaxLength"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "int"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("propertyValues");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PropertyValues"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PropertyValue"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PropertyValue"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("parentValue");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ParentValue"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("dependentProperties");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DependentProperties"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Property"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Property"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("minResolution");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MinResolution"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("maxResolution");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MaxResolution"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("widgets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Widgets"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "DialogWidget"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "DialogWidget"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("termEntity");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TermEntity"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("suggestionEntity");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SuggestionEntity"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("autocompleteProvider");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AutocompleteProvider"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("suggestionProvider");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SuggestionProvider"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("publishSystemId");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PublishSystemId"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("notifications");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Notifications"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PropertyNotification"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PropertyNotification"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("mixedValues");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MixedValues"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
