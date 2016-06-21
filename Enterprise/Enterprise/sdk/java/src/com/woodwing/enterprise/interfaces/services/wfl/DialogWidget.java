/**
 * DialogWidget.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class DialogWidget  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.PropertyInfo propertyInfo;

    private com.woodwing.enterprise.interfaces.services.wfl.PropertyUsage propertyUsage;

    public DialogWidget() {
    }

    public DialogWidget(
           com.woodwing.enterprise.interfaces.services.wfl.PropertyInfo propertyInfo,
           com.woodwing.enterprise.interfaces.services.wfl.PropertyUsage propertyUsage) {
           this.propertyInfo = propertyInfo;
           this.propertyUsage = propertyUsage;
    }


    /**
     * Gets the propertyInfo value for this DialogWidget.
     * 
     * @return propertyInfo
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PropertyInfo getPropertyInfo() {
        return propertyInfo;
    }


    /**
     * Sets the propertyInfo value for this DialogWidget.
     * 
     * @param propertyInfo
     */
    public void setPropertyInfo(com.woodwing.enterprise.interfaces.services.wfl.PropertyInfo propertyInfo) {
        this.propertyInfo = propertyInfo;
    }


    /**
     * Gets the propertyUsage value for this DialogWidget.
     * 
     * @return propertyUsage
     */
    public com.woodwing.enterprise.interfaces.services.wfl.PropertyUsage getPropertyUsage() {
        return propertyUsage;
    }


    /**
     * Sets the propertyUsage value for this DialogWidget.
     * 
     * @param propertyUsage
     */
    public void setPropertyUsage(com.woodwing.enterprise.interfaces.services.wfl.PropertyUsage propertyUsage) {
        this.propertyUsage = propertyUsage;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof DialogWidget)) return false;
        DialogWidget other = (DialogWidget) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.propertyInfo==null && other.getPropertyInfo()==null) || 
             (this.propertyInfo!=null &&
              this.propertyInfo.equals(other.getPropertyInfo()))) &&
            ((this.propertyUsage==null && other.getPropertyUsage()==null) || 
             (this.propertyUsage!=null &&
              this.propertyUsage.equals(other.getPropertyUsage())));
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
        if (getPropertyInfo() != null) {
            _hashCode += getPropertyInfo().hashCode();
        }
        if (getPropertyUsage() != null) {
            _hashCode += getPropertyUsage().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(DialogWidget.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "DialogWidget"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("propertyInfo");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PropertyInfo"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PropertyInfo"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("propertyUsage");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PropertyUsage"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PropertyUsage"));
        elemField.setNillable(false);
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
