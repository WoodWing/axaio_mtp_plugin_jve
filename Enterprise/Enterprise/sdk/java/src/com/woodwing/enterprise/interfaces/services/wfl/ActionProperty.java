/**
 * ActionProperty.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class ActionProperty  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.Action action;

    private com.woodwing.enterprise.interfaces.services.wfl.ObjectType objectType;

    private com.woodwing.enterprise.interfaces.services.wfl.PropertyUsage[] properties;

    public ActionProperty() {
    }

    public ActionProperty(
           com.woodwing.enterprise.interfaces.services.wfl.Action action,
           com.woodwing.enterprise.interfaces.services.wfl.ObjectType objectType,
           com.woodwing.enterprise.interfaces.services.wfl.PropertyUsage[] properties) {
           this.action = action;
           this.objectType = objectType;
           this.properties = properties;
    }


    /**
     * Gets the action value for this ActionProperty.
     * 
     * @return action
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Action getAction() {
        return action;
    }


    /**
     * Sets the action value for this ActionProperty.
     * 
     * @param action
     */
    public void setAction(com.woodwing.enterprise.interfaces.services.wfl.Action action) {
        this.action = action;
    }


    /**
     * Gets the objectType value for this ActionProperty.
     * 
     * @return objectType
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ObjectType getObjectType() {
        return objectType;
    }


    /**
     * Sets the objectType value for this ActionProperty.
     * 
     * @param objectType
     */
    public void setObjectType(com.woodwing.enterprise.interfaces.services.wfl.ObjectType objectType) {
        this.objectType = objectType;
    }


    /**
     * Gets the properties value for this ActionProperty.
     * 
     * @return properties
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PropertyUsage[] getProperties() {
        return properties;
    }


    /**
     * Sets the properties value for this ActionProperty.
     * 
     * @param properties
     */
    public void setProperties(com.woodwing.enterprise.interfaces.services.wfl.PropertyUsage[] properties) {
        this.properties = properties;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ActionProperty)) return false;
        ActionProperty other = (ActionProperty) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.action==null && other.getAction()==null) || 
             (this.action!=null &&
              this.action.equals(other.getAction()))) &&
            ((this.objectType==null && other.getObjectType()==null) || 
             (this.objectType!=null &&
              this.objectType.equals(other.getObjectType()))) &&
            ((this.properties==null && other.getProperties()==null) || 
             (this.properties!=null &&
              java.util.Arrays.equals(this.properties, other.getProperties())));
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
        if (getAction() != null) {
            _hashCode += getAction().hashCode();
        }
        if (getObjectType() != null) {
            _hashCode += getObjectType().hashCode();
        }
        if (getProperties() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getProperties());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getProperties(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(ActionProperty.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ActionProperty"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("action");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Action"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Action"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objectType");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ObjectType"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ObjectType"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("properties");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Properties"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PropertyUsage"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "PropertyUsage"));
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
